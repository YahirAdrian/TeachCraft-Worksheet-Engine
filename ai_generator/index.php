<?php

require 'vendor/autoload.php';

use AIGenerator\LessonContext;
use AIGenerator\OpenAIProvider;
use AIGenerator\PromptBuilder;
use AIGenerator\ResponseValidator;
use AIGenerator\SchemaLoader;
use AIGenerator\WorksheetGenerator;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? null) ?: ($_SERVER['OPENAI_API_KEY'] ?? null);

if (!$apiKey) {
    throw new RuntimeException('OPENAI_API_KEY is missing from the environment.');
}

$schema_path = __DIR__ . '/data/schema.json';
$lesson_path = __DIR__ . '/data/lesson.json';
$output_path = __DIR__ . '/responses/content.json';

try {
    $context = LessonContext::fromFile($lesson_path);

    $generator = new WorksheetGenerator(
        new SchemaLoader(),
        new PromptBuilder(),
        new OpenAIProvider($apiKey),
        new ResponseValidator(),
    );

    $startTime = microtime(true);

    $data = $generator->generate($schema_path, $context);

    $endTime = microtime(true);

    if (!is_dir(dirname($output_path))) {
        mkdir(dirname($output_path), 0775, true);
    }

    $result = file_put_contents(
        $output_path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    if ($result === false) {
        throw new RuntimeException("Unable to write the worksheet file: {$output_path}");
    }

    $executionTime = $endTime - $startTime;

    echo 'Worksheet content generated successfully!' . PHP_EOL;
    echo "Output: {$output_path}" . PHP_EOL;
    echo 'Generated in: ' . round($executionTime, 2) . ' seconds' . PHP_EOL;
} catch (Exception $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
