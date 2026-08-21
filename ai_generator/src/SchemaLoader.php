<?php

namespace AIGenerator;

use RuntimeException;

/**
 * Loads and validates a template schema.json produced by the Template
 * Inspector, ensuring it contains the sections the generator relies on.
 */
final class SchemaLoader
{
    private const REQUIRED_KEYS = [
        'template',
        'requirements',
        'output_schema',
    ];

    /**
     * Load a schema.json file and validate its structure.
     *
     * @param string $path Path to the schema.json file
     * @return array The decoded schema
     * @throws RuntimeException If the file is missing, invalid, or incomplete
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("Schema not found: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read the schema file: {$path}");
        }

        try {
            $schema = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('The schema file contains invalid JSON.', 0, $e);
        }

        if (!is_array($schema)) {
            throw new RuntimeException('The schema file must contain a JSON object.');
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $schema)) {
                throw new RuntimeException("The schema is missing the required key '{$key}'.");
            }
        }

        return $schema;
    }
}
