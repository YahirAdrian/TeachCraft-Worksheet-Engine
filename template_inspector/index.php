<?php

require 'vendor/autoload.php';

use TemplateInspector\TemplateInspector;

$template_path = __DIR__ . '/templates/Guess-Who-template.pptx';
$metadata_path = __DIR__ . '/data/metadata.json';
$output_path = __DIR__ . '/output/schema.json';

try {
    $inspector = new TemplateInspector();
    $schema = $inspector->inspect($template_path, $metadata_path);

    if (!is_dir(dirname($output_path))) {
        mkdir(dirname($output_path), 0775, true);
    }

    $result = file_put_contents(
        $output_path,
        json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    if ($result === false) {
        throw new RuntimeException(
            "Unable to write the schema file: {$output_path}"
        );
    }

    echo 'Schema generated successfully!' . PHP_EOL;
    echo "Output: {$output_path}" . PHP_EOL;

    if ($schema['warnings'] !== []) {
        echo PHP_EOL . 'Warnings:' . PHP_EOL;

        foreach ($schema['warnings'] as $warning) {
            echo '  - ' . $warning . PHP_EOL;
        }
    }
} catch (Exception $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
