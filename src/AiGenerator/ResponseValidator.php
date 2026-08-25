<?php

namespace AIGenerator;

use RuntimeException;

/**
 * Validates an AI-generated response against the template's output schema by
 * checking that required keys exist and that their values match the expected
 * types and structure.
 */
final class ResponseValidator
{
    /**
     * Validate a decoded response against the output schema.
     *
     * @param array $response The decoded AI response
     * @param array $outputSchema The output_schema from the template
     * @return void
     * @throws RuntimeException If the response does not match the schema
     */
    public function validate(array $response, array $outputSchema): void
    {
        $this->validateLevel($response, $outputSchema, '$');
    }

    private function validateLevel(array $value, array $schema, string $path): void
    {
        foreach ($schema as $key => $expectedType) {
            $currentPath = "{$path}.{$key}";

            if (!array_key_exists($key, $value)) {
                throw new RuntimeException(
                    "Missing required key '{$key}' at {$currentPath}."
                );
            }

            $this->validateValue($value[$key], $expectedType, $currentPath);
        }
    }

    private function validateValue(mixed $value, mixed $expectedType, string $path): void
    {
        if ($expectedType === 'string') {
            if (!is_string($value)) {
                throw new RuntimeException(
                    "'{$path}' must be a string."
                );
            }

            return;
        }

        if (!is_array($expectedType)) {
            return;
        }

        if (array_is_list($expectedType)) {
            $this->validateList($value, $expectedType[0], $path);

            return;
        }

        $this->validateObject($value, $expectedType, $path);
    }

    private function validateList(mixed $value, mixed $itemSchema, string $path): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new RuntimeException(
                "'{$path}' must be an array."
            );
        }

        if ($itemSchema === 'string') {
            foreach ($value as $item) {
                if (!is_string($item)) {
                    throw new RuntimeException(
                        "Items of '{$path}' must be strings."
                    );
                }
            }

            return;
        }

        if (is_array($itemSchema)) {
            foreach ($value as $item) {
                if (!is_array($item)) {
                    throw new RuntimeException(
                        "Items of '{$path}' must be objects."
                    );
                }

                $this->validateLevel($item, $itemSchema, $path . '[]');
            }
        }
    }

    private function validateObject(mixed $value, array $objectSchema, string $path): void
    {
        if (!is_array($value)) {
            throw new RuntimeException(
                "'{$path}' must be an object."
            );
        }

        $this->validateLevel($value, $objectSchema, $path);
    }
}
