<?php

namespace AIGenerator;

/**
 * OpenAI implementation of the AI provider interface.
 */
final class OpenAIProvider implements AIProviderInterface
{
    private \OpenAI\Client $client;

    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-5-mini',
        private string $reasoningEffort = 'low',
    ) {
        $this->client = \OpenAI::client($this->apiKey);
    }

    public function generate(string $systemPrompt, string $userPrompt): string
    {
        $response = $this->client->responses()->create([
            'model' => $this->model,
            'reasoning' => [
                'effort' => $this->reasoningEffort,
            ],
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $systemPrompt,
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $userPrompt,
                        ],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_object',
                ],
            ],
        ]);

        return $response->outputText;
    }
}
