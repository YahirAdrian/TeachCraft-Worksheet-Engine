<?php

require 'vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);

$dotenv->load();

$apiKey = getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? null) ?: ($_SERVER['OPENAI_API_KEY'] ?? null);

if (!$apiKey) {
    throw new RuntimeException('OPENAI_API_KEY is missing from the environment.');
}

$client = OpenAI::client($apiKey);

$request = require 'prompts/guess-who.php';

$systemPrompt = <<<PROMPT
You are an educational worksheet generator.

Return ONLY valid JSON.

Do not use Markdown.

Do not explain anything.

Do not wrap the JSON in code fences.

Use worksheet values to get info about the worksheet description, instructions, and prompt instructions.

Use lesson values to get info about the language, CEFR level, topic, and grammar.

Use teacher values to get info about the teacher instructions.

Use template values to get info about the template id and requirements.

Use output_schema values to get info about the output schema. This indicates how the json output should be structured.
PROMPT;

$userPrompt = json_encode($request, JSON_PRETTY_PRINT);

$startTime = microtime(true);

$response = $client->responses()->create([
    'model' => 'gpt-5-mini',
    'reasoning' => [
        'effort' => 'low',
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

$json = $response->outputText;

echo $json; 

$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Invalid JSON returned.");
}

file_put_contents(
    'responses/worksheet.json',
    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

$endTime = microtime(true);

$executionTime = $endTime - $startTime;

echo "Worksheet generated successfully!\n";
echo "Generated in: " . round($executionTime, 2) . " seconds\n";
