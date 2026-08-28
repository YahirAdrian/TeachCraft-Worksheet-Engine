<?php

declare(strict_types=1);

namespace TemplateEngine;

use RuntimeException;

class TemplateProcessor
{

    private array $repeatPositions = [];
    private array $rbindPositions = [];
    private array $assetRegistry = [];
    private array $mediaByValue = [];
    private ?MediaManager $mediaManager;

    public function __construct(
        private Slide $slide,
        private ?OpenXMLPackage $package = null,
        private ?AssetResolverInterface $resolver = null,
        private int $slideNumber = 1,
        ?MediaManager $mediaManager = null,
    ) {
        if ($mediaManager !== null) {
            $this->mediaManager = $mediaManager;

            return;
        }

        $this->mediaManager = $package === null ? null : new MediaManager($package);
    }

    public function process(JSONContext $context): void
    {
        
        $shapes = $this->slide->getShapes();

        foreach ($shapes as $shape) {
            $this->processShape($shape,$context);
        }
    }

    private function processShape(Shape $shape, JSONContext $context, ?array $repeatState = null): void {
        $name = trim($shape->getName());
        //TODO: Convert this to a switch statement
        if (str_starts_with($name, 'repeat:')) {
            $this->processRepeat($shape, $context, $repeatState);

            return;
        }

        if (str_starts_with($name, 'bind:')) {
            $this->processBind($shape, $context);

            return;
        }

        if(str_starts_with($name, 'rbind:')) {
            $this->processRBind($shape, $context, $repeatState);

            return;
        }

        if(str_starts_with($name, 'lookup:')) {
            $this->processLookup($shape, $context);

            return;
        }

        if ($shape->isGroup()) {
            foreach ($shape->getChildren() as $child) {
                $this->processShape($child, $context, $repeatState);
            }
        }
    }

    private function processRepeat(Shape $shape, JSONContext $parentContext, ?array $repeatState = null): void {
        echo "Processing {$shape->getName()}"
            . PHP_EOL;

        $this->rbindPositions = [];
        
        if (!$shape->isGroup()) {
            throw new RuntimeException(
                "The directive '{$shape->getName()}' "
                . 'must belong to a group.'
            );
        }

        $directive = trim($shape->getName());

        $path = trim(
            substr($directive, strlen('repeat:'))
        );

        if ($path === '') {
            throw new RuntimeException(
                'Repeat path cannot be empty.'
            );
        }

        $items = $parentContext->getArray($path);

        $index = $this->repeatPositions[$path] ?? 0;

        /*
         * Advance before processing so every template occurrence
         * has a unique array position.
         */
        $this->repeatPositions[$path] = $index + 1;

        if (!array_key_exists($index, $items)) {
            echo "Warning: No item {$index} for {$directive}."
                . PHP_EOL;

            return;
        }

        $item = $items[$index];

        if (!is_array($item)) {
            throw new RuntimeException(
                "{$directive}[{$index}] must be an object."
            );
        }

        $itemContext = new JSONContext($item);
        $repeatState = [
            'path' => $path,
            'index' => $index,
        ];

        foreach ($shape->getChildren() as $child) {
            $this->processShape($child, $itemContext, $repeatState);
        }

        echo "Filled {$directive}[{$index}]"
            . PHP_EOL;
    }

    private function processRBind(Shape $shape, JSONContext $context, ?array $repeatState = null): void {
        $directive = trim($shape->getName());

        $path = trim(
            substr($directive, strlen('rbind:'))
        );

        if ($path === '') {
            echo "Warning: Empty repeated binding path."
                . PHP_EOL;

            return;
        }

        if ($repeatState === null || !isset($repeatState['path'], $repeatState['index'])) {
            echo "Warning: {$directive} is not inside a repeat block."
                . PHP_EOL;

            return;
        }

        $item = $context->getData();

        if (!is_array($item)) {
            echo "Warning: {$directive} is not bound to an object."
                . PHP_EOL;

            return;
        }

        $values = $item[$path] ?? null;

        if (!is_array($values)) {
            echo "Warning: No array value for {$directive}."
                . PHP_EOL;

            return;
        }

        $position = $this->rbindPositions[$directive] ?? 0;
        $this->rbindPositions[$directive] = $position + 1;

        if (!array_key_exists($position, $values)) {
            echo "Warning: No item {$position} for {$directive}."
                . PHP_EOL;

            return;
        }

        $value = $values[$position];

        if ($value === null) {
            echo "Warning: No value for {$directive}."
                . PHP_EOL;

            return;
        }

        if (!is_scalar($value)) {
            echo "Warning: {$directive} is not scalar."
                . PHP_EOL;

            return;
        }

        $shape->setText((string) $value);

        echo "Bound {$directive} => {$value}"
            . PHP_EOL;
    }

    private function processBind(Shape $shape, JSONContext $context): void {
        $directive = trim($shape->getName());

        $path = trim(
            substr($directive, strlen('bind:'))
        );

        if ($path === '') {
            echo "Warning: Empty binding path."
                . PHP_EOL;

            return;
        }

        $value = $context->get($path);

        if ($value === null) {
            echo "Warning: No value for {$directive}."
                . PHP_EOL;

            return;
        }

        if (!is_scalar($value)) {
            echo "Warning: {$directive} is not scalar."
                . PHP_EOL;

            return;
        }

        $shape->setText((string) $value);

        echo "Bound {$directive} => {$value}"
            . PHP_EOL;
    }

    private function processLookup(Shape $shape, JSONContext $context): void {
        $directive = trim($shape->getName());

        $field = trim(
            substr($directive, strlen('lookup:'))
        );

        if ($field === '') {
            echo "Warning: Empty lookup field."
                . PHP_EOL;

            return;
        }

        if (str_ends_with($field, '*')) {
            $field = substr($field, 0, -1);
        }

        if ($this->mediaManager === null || $this->resolver === null) {
            echo "Warning: {$directive} requires a package, resolver, and slide number."
                . PHP_EOL;

            return;
        }

        if (!$shape->isPicture()) {
            echo "Warning: {$directive} must belong to a picture shape."
                . PHP_EOL;

            return;
        }

        if (isset($this->assetRegistry[$field])) {
            $entry = $this->assetRegistry[$field];

            $shape->setImageReference($entry['rid']);

            echo "Reused {$directive} => {$entry['value']}"
                . PHP_EOL;

            return;
        }

        $value = $context->get($field);

        if ($value === null || !is_string($value) || $value === '') {
            echo "Warning: No value for {$directive}."
                . PHP_EOL;

            return;
        }

        $assetPath = $this->resolver->resolve($value);

        if ($assetPath === null) {
            echo "Warning: No asset for '{$value}'."
                . PHP_EOL;

            return;
        }

        $mediaFile = 'lookup_' . basename($assetPath);

        if (!isset($this->mediaByValue[$value])) {
            $contents = file_get_contents($assetPath);

            if ($contents === false) {
                echo "Warning: Unable to read asset '{$assetPath}'."
                    . PHP_EOL;

                return;
            }

            $this->mediaManager->ensureSvgContentType();
            $this->mediaManager->addMedia($mediaFile, $contents);
            $this->mediaByValue[$value] = $mediaFile;
        }

        $relationshipId = $this->mediaManager->ensureRelationship(
            $this->slideNumber,
            $this->mediaByValue[$value]
        );

        $this->assetRegistry[$field] = [
            'value' => $value,
            'rid' => $relationshipId,
        ];

        $shape->setImageReference($relationshipId);

        echo "Bound {$directive} => {$value}"
            . PHP_EOL;
    }
}