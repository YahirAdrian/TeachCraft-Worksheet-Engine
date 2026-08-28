<?php

require __DIR__ . '/../vendor/autoload.php';

use TemplateEngine\JSONContext;
use TemplateEngine\MediaManager;
use TemplateEngine\OpenMojiResolver;
use TemplateEngine\OpenXMLPackage;
use TemplateEngine\Slide;
use TemplateEngine\TemplateProcessor;
use TemplateInspector\SchemaBuilder;
use TemplateInspector\Slide as InspectorSlide;
use TemplateInspector\TemplateValidator;

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function expectSame(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(
            STDERR,
            "{$label}\n  Expected: " . json_encode($expected) . "\n  Got:      " . json_encode($actual) . PHP_EOL
        );
        exit(1);
    }
}

function buildSlideXml(string $titleDirective, array $binds): string
{
    $bindsXml = '';

    foreach ($binds as $name => $text) {
        $bindsXml .= "      <p:sp>\n"
            . "        <p:nvSpPr><p:cNvPr id=\"{$name}\" name=\"bind:{$name}\" /></p:nvSpPr>\n"
            . "        <p:txBody>\n"
            . "          <a:p><a:r><a:t>{$text}</a:t></a:r></a:p>\n"
            . "        </p:txBody>\n"
            . "      </p:sp>\n";
    }

    return <<<XML
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="999" name="{$titleDirective}" /></p:nvSpPr>
        <p:txBody>
          <a:p><a:r><a:t>placeholder</a:t></a:r></a:p>
        </p:txBody>
      </p:sp>
{$bindsXml}    </p:spTree>
  </p:cSld>
</p:sld>
XML;
}

$workDir = sys_get_temp_dir() . '/multislide_test_' . getmypid();
@mkdir($workDir, 0777, true);

$iconsDir = $workDir . '/icons';
mkdir($iconsDir, 0777, true);
file_put_contents($iconsDir . '/2615.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
file_put_contents($iconsDir . '/1F68C.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

$pptxPath = $workDir . '/test.pptx';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/ppt/slides/slide1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>'
    . '<Override PartName="/ppt/slides/slide2.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>'
    . '<Override PartName="/ppt/slides/slide3.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>'
    . '</Types>';

$slide1Xml = buildSlideXml('bind:title', ['label' => 'Slide 1 text']);
$slide2Xml = buildSlideXml('bind:title', ['label' => 'Slide 2 text']);
$slide3Xml = buildSlideXml('bind:title', ['label' => 'Slide 3 text']);

$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/placeholder.png"/>'
    . '</Relationships>';

$zip = new ZipArchive();
if ($zip->open($pptxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fail('Unable to create test pptx.');
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('ppt/slides/slide1.xml', $slide1Xml);
$zip->addFromString('ppt/slides/slide2.xml', $slide2Xml);
$zip->addFromString('ppt/slides/slide3.xml', $slide3Xml);
$zip->addFromString('ppt/slides/_rels/slide1.xml.rels', $relsXml);
$zip->addFromString('ppt/slides/_rels/slide2.xml.rels', $relsXml);
$zip->addFromString('ppt/slides/_rels/slide3.xml.rels', $relsXml);
$zip->close();

$package = new OpenXMLPackage($pptxPath);
expectSame(3, $package->getSlideCount(), 'getSlideCount() should report three slides');

$resolver = new OpenMojiResolver($iconsDir);

$slideContents = [
    ['title' => 'Slide One Title', 'label' => 'Slide One Label'],
    ['title' => 'Slide Two Title', 'label' => 'Slide Two Label'],
    ['title' => 'Slide Three Title', 'label' => 'Slide Three Label'],
];

$mediaManager = new MediaManager($package);

for ($slideNumber = 1; $slideNumber <= $package->getSlideCount(); $slideNumber++) {
    $slide = new Slide($package->getSlideXML($slideNumber));
    $processor = new TemplateProcessor($slide, $package, $resolver, $slideNumber, $mediaManager);
    $processor->process(new JSONContext($slideContents[$slideNumber - 1]));
    $package->replaceSlide($slideNumber, $slide->getXml());
}

$package->close();

$zip = new ZipArchive();
$zip->open($pptxPath);

for ($slideNumber = 1; $slideNumber <= 3; $slideNumber++) {
    $expectedTitle = $slideContents[$slideNumber - 1]['title'];
    $expectedLabel = $slideContents[$slideNumber - 1]['label'];

    $xml = $zip->getFromName("ppt/slides/slide{$slideNumber}.xml");

    if ($xml === false) {
        fail("slide{$slideNumber}.xml is missing from the package.");
    }

    if (!str_contains($xml, "<a:t>{$expectedTitle}</a:t>")) {
        fail("slide{$slideNumber}.xml should contain '{$expectedTitle}' but does not.");
    }

    if (!str_contains($xml, "<a:t>{$expectedLabel}</a:t>")) {
        fail("slide{$slideNumber}.xml should contain '{$expectedLabel}' but does not.");
    }

    if (str_contains($xml, '<a:t>placeholder</a:t>')) {
        fail("slide{$slideNumber}.xml still contains a placeholder.");
    }
}

$zip->close();

$validator = new TemplateValidator();
$builder = new SchemaBuilder($validator);

$inspectorSlides = [];
for ($i = 1; $i <= 3; $i++) {
    $inspectorSlides[] = new InspectorSlide(buildSlideXml('bind:title', ['label' => "Slide {$i} text"]));
}

$schema = $builder->build($inspectorSlides);

expectSame(
    [
        'slides' => [
            [
                'title' => 'string',
                'label' => 'string',
            ],
            [
                'title' => 'string',
                'label' => 'string',
            ],
            [
                'title' => 'string',
                'label' => 'string',
            ],
        ],
    ],
    $schema,
    'Inspector schema should describe a slides array with one entry per slide'
);

expectSame(
    ['slides' => 3],
    $builder->getRequirements(),
    'Inspector should report the slide count in requirements'
);

expectSame([], $validator->getWarnings(), 'Inspector should produce no warnings for a clean multi-slide template');

@unlink($pptxPath);
@unlink($iconsDir . '/2615.svg');
@unlink($iconsDir . '/1F68C.svg');
@rmdir($iconsDir);
@rmdir($workDir);

fwrite(STDOUT, "Multi-slide test passed." . PHP_EOL);
