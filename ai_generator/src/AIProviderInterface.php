<?php

namespace AIGenerator;

/**
 * Contract for any AI provider used to generate worksheet content.
 *
 * Implementing this interface keeps the generator provider-independent so
 * that different providers can be swapped without changing the generator.
 */
interface AIProviderInterface
{
    /**
     * Generate a JSON string response for the given prompts.
     *
     * @param string $systemPrompt The system instructions
     * @param string $userPrompt The JSON-encoded user request
     * @return string The raw JSON output from the provider
     */
    public function generate(string $systemPrompt, string $userPrompt): string;
}
