<?php

namespace AIGenerator;

/**
 * Builds the system and user prompts sent to the AI provider by combining
 * the template schema with the teacher-supplied lesson context.
 */
final class PromptBuilder
{
    /**
     * Build the system prompt describing how to interpret the request.
     *
     * @return string
     */
    public function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
        You are an educational worksheet generator.

        Return ONLY valid JSON.

        Do not use Markdown.

        Do not explain anything.

        Do not wrap the JSON in code fences.

        Use template values to get info about the worksheet title, description, instructions, and prompt instructions.

        Use requirements values to get info about how many items and collections are required.

        Use output_schema values to get info about the output schema. This indicates how the json output should be structured.

        Use constraints values to respect length and formatting limits.

        Fields typed as emoji_unicode must contain an emoji's Unicode code point in uppercase hexadecimal without the 'U+' prefix. Use '-' to separate multiple code points in a sequence (e.g., '2615', '1F68C', '1F9D1-200D-1F373').

        Use lesson values to get info about the language, CEFR level, topic, and grammar.

        Use teacher values to get info about the teacher instructions.
        PROMPT;
    }

    /**
     * Build the user prompt by merging the schema with the lesson context.
     *
     * @param array $schema The decoded template schema
     * @param LessonContext $context The teacher-supplied inputs
     * @return string JSON-encoded user prompt
     */
    public function buildUserPrompt(array $schema, LessonContext $context): string
    {
        $request = [
            'template' => $schema['template'],
            'requirements' => $schema['requirements'],
            'output_schema' => $schema['output_schema'],
            'constraints' => $schema['constraints'] ?? [],
            'lesson' => $context->getLesson(),
            'teacher' => $context->getTeacher(),
        ];

        return json_encode(
            $request,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
