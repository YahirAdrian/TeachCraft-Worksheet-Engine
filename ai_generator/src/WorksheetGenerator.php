<?php

namespace AIGenerator;

use RuntimeException;

/**
 * Orchestrates worksheet content generation: loads the template schema and
 * the lesson context, builds the prompts, delegates to an AI provider, and
 * validates the returned response against the output schema.
 */
final class WorksheetGenerator
{
    public function __construct(
        private SchemaLoader $schemaLoader,
        private PromptBuilder $promptBuilder,
        private AIProviderInterface $provider,
        private ResponseValidator $validator,
    ) {
    }

    /**
     * Generate worksheet content for a template and lesson.
     *
     * @param string $schemaPath Path to the template schema.json
     * @param LessonContext $context The teacher-supplied lesson inputs
     * @return array The validated worksheet content
     * @throws RuntimeException If generation or validation fails
     */
    public function generate(string $schemaPath, LessonContext $context): array
    {
        $schema = $this->schemaLoader->load($schemaPath);

        $systemPrompt = $this->promptBuilder->buildSystemPrompt();
        $userPrompt = $this->promptBuilder->buildUserPrompt($schema, $context);

        $output = $this->provider->generate($systemPrompt, $userPrompt);

        try {
            $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('The AI provider returned invalid JSON.', 0, $e);
        }

        if (!is_array($data)) {
            throw new RuntimeException('The AI provider did not return a JSON object.');
        }

        $this->validator->validate($data, $schema['output_schema']);

        return $data;
    }
}
