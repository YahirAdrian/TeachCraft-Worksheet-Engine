<?php

namespace TemplateInspector;

use RuntimeException;

/**
 * Orchestrates the inspection of a PowerPoint template: opens the package,
 * walks every slide, builds the output schema and requirements, joins them
 * with the companion template metadata, and produces the final schema.
 */
final class TemplateInspector
{
    public function __construct()
    {
    }

    /**
     * Inspect a template and build the content schema.
     *
     * @param string $pptxPath Path to the .pptx template
     * @param string $metadataPath Path to the companion metadata.json file
     * @return array The assembled schema (template, requirements, output_schema)
     */
    public function inspect(string $pptxPath, string $metadataPath): array
    {
        $this->assertFile($pptxPath, 'Template');
        $this->assertFile($metadataPath, 'Metadata');

        $package = new OpenXMLPackage($pptxPath);
        $slideCount = $package->getSlideCount();

        if ($slideCount === 0) {
            $package->close();

            throw new RuntimeException('The template contains no slides.');
        }

        $slides = [];

        for ($i = 1; $i <= $slideCount; $i++) {
            $slides[] = new Slide($package->getSlideXML($i));
        }

        $package->close();

        $validator = new TemplateValidator();
        $builder = new SchemaBuilder($validator);

        $outputSchema = $builder->build($slides);

        return [
            'template' => $this->loadMetadata($metadataPath),
            'requirements' => $builder->getRequirements(),
            'output_schema' => $outputSchema,
            'warnings' => $validator->getWarnings(),
        ];
    }

    private function assertFile(string $path, string $label): void
    {
        if (!is_file($path)) {
            throw new RuntimeException("{$label} not found: {$path}");
        }
    }

    private function loadMetadata(string $metadataPath): array
    {
        $contents = file_get_contents($metadataPath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read the metadata file: {$metadataPath}");
        }

        $metadata = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($metadata)) {
            throw new RuntimeException('The metadata file must contain a JSON object.');
        }

        return $metadata;
    }
}
