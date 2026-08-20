<?php

require __DIR__ . '/../vendor/autoload.php';

use TemplateEngine\JSONContext;
use TemplateEngine\Slide;
use TemplateEngine\TemplateProcessor;

$xml = <<<'XML'
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:grpSp>
        <p:nvGrpSpPr>
          <p:cNvPr id="1" name="repeat:characters" />
        </p:nvGrpSpPr>
        <p:grpSpPr />
        <p:sp>
          <p:nvSpPr>
            <p:cNvPr id="2" name="rbind:answers" />
          </p:nvSpPr>
          <p:txBody>
            <a:p><a:r><a:t>placeholder</a:t></a:r></a:p>
          </p:txBody>
        </p:sp>
        <p:sp>
          <p:nvSpPr>
            <p:cNvPr id="3" name="rbind:answers" />
          </p:nvSpPr>
          <p:txBody>
            <a:p><a:r><a:t>placeholder</a:t></a:r></a:p>
          </p:txBody>
        </p:sp>
      </p:grpSp>
      <p:grpSp>
        <p:nvGrpSpPr>
          <p:cNvPr id="4" name="repeat:characters" />
        </p:nvGrpSpPr>
        <p:grpSpPr />
        <p:sp>
          <p:nvSpPr>
            <p:cNvPr id="5" name="rbind:answers" />
          </p:nvSpPr>
          <p:txBody>
            <a:p><a:r><a:t>placeholder</a:t></a:r></a:p>
          </p:txBody>
        </p:sp>
        <p:sp>
          <p:nvSpPr>
            <p:cNvPr id="6" name="rbind:answers" />
          </p:nvSpPr>
          <p:txBody>
            <a:p><a:r><a:t>placeholder</a:t></a:r></a:p>
          </p:txBody>
        </p:sp>
      </p:grpSp>
    </p:spTree>
  </p:cSld>
</p:sld>
XML;

$slide = new Slide($xml);
$processor = new TemplateProcessor($slide);
$context = new JSONContext([
    'characters' => [
        [
            'answers' => ['answer1', 'answer2'],
        ],
        [
            'answers' => ['answer3', 'answer4'],
        ],
    ],
]);

$processor->process($context);

$groups = $slide->getShapes();
$firstTexts = array_map(static fn($shape) => $shape->getText(), $groups[0]->getChildren());
$secondTexts = array_map(static fn($shape) => $shape->getText(), $groups[1]->getChildren());

if ($firstTexts !== ['answer1', 'answer2']) {
    fwrite(STDERR, "Expected first group ['answer1', 'answer2'], got " . json_encode($firstTexts) . PHP_EOL);
    exit(1);
}

if ($secondTexts !== ['answer3', 'answer4']) {
    fwrite(STDERR, "Expected second group ['answer3', 'answer4'], got " . json_encode($secondTexts) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Rbind regression test passed." . PHP_EOL);
