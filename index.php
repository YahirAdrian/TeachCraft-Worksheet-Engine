<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use TemplateInspector\TemplateInspector;
use AIGenerator\LessonContext;
use AIGenerator\OpenAIProvider;
use AIGenerator\PromptBuilder;
use AIGenerator\ResponseValidator;
use AIGenerator\SchemaLoader;
use AIGenerator\WorksheetGenerator;
use TemplateEngine\OpenXMLPackage;
use TemplateEngine\Slide;
use TemplateEngine\JSONContext;
use TemplateEngine\TemplateProcessor;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? null) ?: ($_SERVER['OPENAI_API_KEY'] ?? null);

if (!$apiKey) {
    throw new RuntimeException('OPENAI_API_KEY is missing from the environment.');
}

$root = __DIR__;
$templatePath = "{$root}/template/input/template.pptx";
$metadataPath = "{$root}/template/input/metadata.json";
$lessonPath = "{$root}/template/input/lesson.json";
$schemaPath = "{$root}/template/schema/schema.json";
$contentPath = "{$root}/template/content/content.json";
$resultPath = "{$root}/template/result/generated.pptx";

try {
    echo '=== Stage 1: Template Inspector ===' . PHP_EOL;
    echo 'Inspecting template...' . PHP_EOL;

    $inspector = new TemplateInspector();
    $schema = $inspector->inspect($templatePath, $metadataPath);

    if (!is_dir(dirname($schemaPath))) {
        mkdir(dirname($schemaPath), 0775, true);
    }

    $result = file_put_contents(
        $schemaPath,
        json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    if ($result === false) {
        throw new RuntimeException("Unable to write the schema file: {$schemaPath}");
    }

    echo 'Schema generated: ' . $schemaPath . PHP_EOL;

    if ($schema['warnings'] !== []) {
        echo PHP_EOL . 'Warnings:' . PHP_EOL;

        foreach ($schema['warnings'] as $warning) {
            echo '  - ' . $warning . PHP_EOL;
        }
    }

    echo PHP_EOL;

    echo '=== Stage 2: AI Generator ===' . PHP_EOL;
    echo 'Generating content with AI...' . PHP_EOL;

    $context = LessonContext::fromFile($lessonPath);

    $generator = new WorksheetGenerator(
        new SchemaLoader(),
        new PromptBuilder(),
        new OpenAIProvider($apiKey),
        new ResponseValidator(),
    );

    $startTime = microtime(true);
    $data = $generator->generate($schemaPath, $context);
    $endTime = microtime(true);

    if (!is_dir(dirname($contentPath))) {
        mkdir(dirname($contentPath), 0775, true);
    }

    $result = file_put_contents(
        $contentPath,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    if ($result === false) {
        throw new RuntimeException("Unable to write the content file: {$contentPath}");
    }

    $executionTime = $endTime - $startTime;

    echo 'Content generated: ' . $contentPath . PHP_EOL;
    echo 'Generated in: ' . round($executionTime, 2) . ' seconds' . PHP_EOL;
    echo PHP_EOL;

    echo '=== Stage 3: Template Engine ===' . PHP_EOL;
    echo 'Binding content to template...' . PHP_EOL;

    if (!is_file($templatePath)) {
        throw new RuntimeException("Template not found: {$templatePath}");
    }

    if (!is_file($contentPath)) {
        throw new RuntimeException("Content file not found: {$contentPath}");
    }

    if (!is_dir(dirname($resultPath))) {
        mkdir(dirname($resultPath), 0775, true);
    }

    if (!copy($templatePath, $resultPath)) {
        throw new RuntimeException('Unable to copy the PPTX template.');
    }

    $jsonContents = file_get_contents($contentPath);

    if ($jsonContents === false) {
        throw new RuntimeException("Unable to read the content file: {$contentPath}");
    }

    $content = json_decode($jsonContents, true, 512, JSON_THROW_ON_ERROR);

    $package = new OpenXMLPackage($resultPath);

    $xml = $package->getSlideXML(1);

    $slide = new Slide($xml);

    $jsonContext = new JSONContext($content);

    $processor = new TemplateProcessor($slide);

    $processor->process($jsonContext);

    $package->replaceSlide(1, $slide->getXml());
    $package->close();

    echo 'Worksheet generated: ' . $resultPath . PHP_EOL;
    echo PHP_EOL;
    echo '=== Pipeline completed successfully ===' . PHP_EOL;

} catch (Exception $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
