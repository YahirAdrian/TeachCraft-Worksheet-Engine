<?php

require __DIR__ . '/../vendor/autoload.php';

use TemplateInspector\SchemaBuilder;
use TemplateInspector\Slide;
use TemplateInspector\TemplateValidator;

function assertSame(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(
            STDERR,
            "{$label}\nExpected: " . json_encode($expected) . "\nGot: " . json_encode($actual) . PHP_EOL
        );
        exit(1);
    }
}

$xml = <<<'XML'
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="1" name="bind:title" /></p:nvSpPr>
      </p:sp>
      <p:pic>
        <p:nvPicPr><p:cNvPr id="2" name="ignore" /></p:nvPicPr>
      </p:pic>
      <p:grpSp>
        <p:nvGrpSpPr><p:cNvPr id="3" name="repeat:characters" /></p:nvGrpSpPr>
        <p:grpSpPr />
        <p:pic>
          <p:nvPicPr><p:cNvPr id="4" name="image:portrait" /></p:nvPicPr>
        </p:pic>
        <p:sp>
          <p:nvSpPr><p:cNvPr id="5" name="rbind:answers" /></p:nvSpPr>
        </p:sp>
      </p:grpSp>
    </p:spTree>
  </p:cSld>
</p:sld>
XML;

$nestedXml = <<<'XML'
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:grpSp>
        <p:nvGrpSpPr><p:cNvPr id="1" name="repeat:clues" /></p:nvGrpSpPr>
        <p:grpSpPr />
        <p:pic>
          <p:nvPicPr><p:cNvPr id="2" name="asset:icon" /></p:nvPicPr>
        </p:pic>
        <p:sp>
          <p:nvSpPr><p:cNvPr id="3" name="bind:label" /></p:nvSpPr>
        </p:sp>
      </p:grpSp>
    </p:spTree>
  </p:cSld>
</p:sld>
XML;

$validator = new TemplateValidator();
$builder = new SchemaBuilder($validator);
$schema = $builder->build([new Slide($xml)]);

assertSame(
    [
        'title' => 'string',
        'characters' => [
            [
                'portrait' => ['prompt' => 'string'],
                'answers' => ['string'],
            ],
        ],
    ],
    $schema,
    'Basic schema mismatch'
);

assertSame(
    [
        'characters' => 1,
        'answers_per_characters' => 1,
    ],
    $builder->getRequirements(),
    'Requirements mismatch'
);

assertSame([], $validator->getWarnings(), 'Expected no warnings for valid template');

$validator2 = new TemplateValidator();
$builder2 = new SchemaBuilder($validator2);
$schema2 = $builder2->build([new Slide($nestedXml)]);

assertSame(
    [
        'clues' => [
            [
                'icon' => 'string',
                'label' => 'string',
            ],
        ],
    ],
    $schema2,
    'Nested schema mismatch'
);

assertSame(
    ['clues' => 1],
    $builder2->getRequirements(),
    'Nested requirements mismatch, asset should not add a requirement'
);

$rbindOutsideXml = <<<'XML'
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="1" name="rbind:answers" /></p:nvSpPr>
      </p:sp>
    </p:spTree>
  </p:cSld>
</p:sld>
XML;

$validator3 = new TemplateValidator();
$builder3 = new SchemaBuilder($validator3);
$schema3 = $builder3->build([new Slide($rbindOutsideXml)]);

assertSame([], $schema3, 'rbind outside a repeat should be skipped');
assertTrue($validator3->hasWarnings(), 'Expected a warning for rbind outside repeat');

function assertTrue(bool $value, string $label): void
{
    if (!$value) {
        fwrite(STDERR, $label . PHP_EOL);
        exit(1);
    }
}

fwrite(STDOUT, "SchemaBuilder test passed." . PHP_EOL);
