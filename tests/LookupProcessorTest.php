<?php

require __DIR__ . '/../vendor/autoload.php';

use TemplateEngine\JSONContext;
use TemplateEngine\OpenMojiResolver;
use TemplateEngine\OpenXMLPackage;
use TemplateEngine\Slide;
use TemplateEngine\TemplateProcessor;

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

$workDir = sys_get_temp_dir() . '/lookup_test_' . getmypid();
@mkdir($workDir, 0777, true);

$iconsDir = $workDir . '/icons';
mkdir($iconsDir, 0777, true);

file_put_contents($iconsDir . '/2615.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
file_put_contents($iconsDir . '/1F68C.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

$pptxPath = $workDir . '/test.pptx';

$contentTypes = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/ppt/slides/slide1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/></Types>
XML;

$slideXml = <<<'XML'
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:pic>
        <p:nvPicPr><p:cNvPr id="1" name="lookup:icon1" /></p:nvPicPr>
        <p:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
        <p:spPr><a:xfrm><a:off x="100" y="200"/><a:ext cx="300" cy="400"/></a:xfrm></p:spPr>
      </p:pic>
      <p:pic>
        <p:nvPicPr><p:cNvPr id="2" name="lookup:icon1*" /></p:nvPicPr>
        <p:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
        <p:spPr><a:xfrm><a:off x="500" y="600"/><a:ext cx="700" cy="800"/></a:xfrm></p:spPr>
      </p:pic>
      <p:pic>
        <p:nvPicPr><p:cNvPr id="3" name="lookup:icon2" /></p:nvPicPr>
        <p:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
        <p:spPr><a:xfrm><a:off x="900" y="1000"/><a:ext cx="1100" cy="1200"/></a:xfrm></p:spPr>
      </p:pic>
    </p:spTree>
  </p:cSld>
</p:sld>
XML;

$relsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/placeholder.png"/></Relationships>
XML;

$zip = new ZipArchive();
if ($zip->open($pptxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fail('Unable to create test pptx.');
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('ppt/slides/slide1.xml', $slideXml);
$zip->addFromString('ppt/slides/_rels/slide1.xml.rels', $relsXml);
$zip->close();

$package = new OpenXMLPackage($pptxPath);
$slide = new Slide($package->getSlideXML(1));
$resolver = new OpenMojiResolver($iconsDir);
$processor = new TemplateProcessor($slide, $package, $resolver, 1);
$context = new JSONContext([
    'icon1' => '2615',
    'icon2' => '1F68C',
]);

$processor->process($context);
$package->replaceSlide(1, $slide->getXml());
$package->close();

$zip = new ZipArchive();
$zip->open($pptxPath);

$outSlide = $zip->getFromName('ppt/slides/slide1.xml');
$outRels = $zip->getFromName('ppt/slides/_rels/slide1.xml.rels');
$outTypes = $zip->getFromName('[Content_Types].xml');
$has2615 = $zip->getFromName('ppt/media/lookup_2615.svg') !== false;
$has1F68C = $zip->getFromName('ppt/media/lookup_1F68C.svg') !== false;
$zip->close();

if (!$has2615) {
    fail('Expected ppt/media/lookup_2615.svg to be added.');
}

if (!$has1F68C) {
    fail('Expected ppt/media/lookup_1F68C.svg to be added.');
}

if (!str_contains($outTypes, 'Extension="svg"') || !str_contains($outTypes, 'image/svg+xml')) {
    fail('Expected the svg content type to be declared.');
}

if (!str_contains($outRels, '../media/lookup_2615.svg') || !str_contains($outRels, '../media/lookup_1F68C.svg')) {
    fail('Expected slide relationships to target both lookup media files.');
}

preg_match('/name="lookup:icon1"[^>]*>.*?r:embed="([^"]+)"/s', $outSlide, $icon1Match);
preg_match('/name="lookup:icon1\*"[^>]*>.*?r:embed="([^"]+)"/s', $outSlide, $icon1StarMatch);
preg_match('/name="lookup:icon2"[^>]*>.*?r:embed="([^"]+)"/s', $outSlide, $icon2Match);

$icon1Rid = $icon1Match[1] ?? null;
$icon1StarRid = $icon1StarMatch[1] ?? null;
$icon2Rid = $icon2Match[1] ?? null;

if ($icon1Rid === null || $icon1StarRid === null || $icon2Rid === null) {
    fail('Unable to read the updated blip embeds.');
}

if ($icon1Rid === 'rId1') {
    fail('The lookup:icon1 blip should no longer reference the placeholder.');
}

if ($icon1Rid !== $icon1StarRid) {
    fail('lookup:icon1 and lookup:icon1* should reuse the same relationship.');
}

if ($icon2Rid === $icon1Rid) {
    fail('lookup:icon2 should use a distinct relationship from lookup:icon1.');
}

if (!str_contains($outSlide, '<a:off x="100" y="200"/><a:ext cx="300" cy="400"/>')) {
    fail('The lookup:icon1 shape geometry was not preserved.');
}

if (!str_contains($outSlide, '<a:off x="900" y="1000"/><a:ext cx="1100" cy="1200"/>')) {
    fail('The lookup:icon2 shape geometry was not preserved.');
}

@unlink($pptxPath);
@unlink($iconsDir . '/2615.svg');
@unlink($iconsDir . '/1F68C.svg');
@rmdir($iconsDir);
@rmdir($workDir);

fwrite(STDOUT, "LookupProcessor test passed." . PHP_EOL);