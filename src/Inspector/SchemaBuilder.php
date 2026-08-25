<?php

namespace TemplateInspector;

use RuntimeException;

/**
 * Walks the PowerPoint shape tree and builds the output content schema and
 * the collection requirements required by the template.
 */
final class SchemaBuilder
{
    private DirectiveParser $parser;
    private array $requirements = [];
    private array $firstGroupRBindFields = [];

    public function __construct(private TemplateValidator $validator)
    {
        $this->parser = new DirectiveParser();
    }

    /**
     * Build the output schema from an array of slides.
     *
     * @param Slide[] $slides The slides to inspect
     * @return array The nested output schema
     */
    public function build(array $slides): array
    {
        $schema = [];
        $this->requirements = [];
        $this->firstGroupRBindFields = [];

        foreach ($slides as $slide) {
            $this->buildShapes($slide->getShapes(), $schema, null, false);
        }

        return $schema;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    private function buildShapes(array $shapes, array &$schema, ?string $repeatField, bool $isFirstGroup): void
    {
        foreach ($shapes as $shape) {
            $this->buildShape($shape, $schema, $repeatField, $isFirstGroup);
        }
    }

    private function buildShape(Shape $shape, array &$schema, ?string $repeatField, bool $isFirstGroup): void
    {
        $name = $shape->getName();
        $directive = $this->parser->parse($name);

        if ($directive === null) {
            if ($this->parser->looksLikeDirective($name)) {
                $this->validator->addWarning(
                    "Unsupported directive shape '{$name}'."
                );
            }

            if ($shape->isGroup()) {
                $this->buildShapes($shape->getChildren(), $schema, $repeatField, $isFirstGroup);
            }

            return;
        }

        switch ($directive->getType()) {
            case DirectiveType::Ignore:
                return;

            case DirectiveType::Bind:
            case DirectiveType::Lookup:
            case DirectiveType::Asset:
                $schema[$directive->getField()] = 'string';
                return;

            case DirectiveType::Image:
                $schema[$directive->getField()] = ['prompt' => 'string'];
                return;

            case DirectiveType::Repeat:
                $this->buildRepeat($shape, $directive, $schema);
                return;

            case DirectiveType::RBind:
                $this->buildRBind($directive, $schema, $repeatField, $isFirstGroup);
                return;
        }
    }

    private function buildRepeat(Shape $shape, Directive $directive, array &$schema): void
    {
        if (!$shape->isGroup()) {
            throw new RuntimeException(
                "The 'repeat' directive '{$directive->getField()}' must belong to a group."
            );
        }

        $field = $directive->getField();

        if ($field === '') {
            throw new RuntimeException('Repeat field cannot be empty.');
        }

        $this->requirements[$field] = ($this->requirements[$field] ?? 0) + 1;

        $isFirstGroup = !array_key_exists($field, $this->firstGroupRBindFields);

        $childSchema = [];

        foreach ($shape->getChildren() as $child) {
            $this->buildShape($child, $childSchema, $field, $isFirstGroup);
        }

        $schema[$field] = [$childSchema];
    }

    private function buildRBind(Directive $directive, array &$schema, ?string $repeatField, bool $isFirstGroup): void
    {
        $field = $directive->getField();

        if ($repeatField === null) {
            $this->validator->addWarning(
                "The 'rbind' directive '{$field}' is not inside a repeat group."
            );

            return;
        }

        if ($field === '') {
            $this->validator->addWarning('Rbind field cannot be empty.');

            return;
        }

        $schema[$field] = ['string'];

        if ($isFirstGroup) {
            $key = "{$field}_per_{$repeatField}";
            $this->requirements[$key] = ($this->requirements[$key] ?? 0) + 1;
            $this->firstGroupRBindFields[$repeatField] = true;
        }
    }
}
