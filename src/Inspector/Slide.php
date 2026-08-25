<?php

namespace TemplateInspector;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

final class Slide
{
    private DOMDocument $document;
    private DOMXPath $xpath;

    public function __construct(private string $xml)
    {
        $this->document = new DOMDocument();

        if ($this->document->loadXML($this->xml) === false) {
            throw new RuntimeException('Unable to parse slide XML.');
        }

        $this->xpath = new DOMXPath($this->document);

        $this->registerNamespaces();
    }

    private function registerNamespaces(): void
    {
        $this->xpath->registerNamespace(
            'p',
            'http://schemas.openxmlformats.org/presentationml/2006/main'
        );

        $this->xpath->registerNamespace(
            'a',
            'http://schemas.openxmlformats.org/drawingml/2006/main'
        );
    }

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }

    public function getXPath(): DOMXPath
    {
        return $this->xpath;
    }

    public function getShapes(): array
    {
        $nodes = $this->xpath->query(
            '/p:sld/p:cSld/p:spTree/p:sp'
            . ' | /p:sld/p:cSld/p:spTree/p:grpSp'
            . ' | /p:sld/p:cSld/p:spTree/p:pic'
            . ' | /p:sld/p:cSld/p:spTree/p:graphicFrame'
        );

        if ($nodes === false) {
            return [];
        }

        $shapes = [];

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $shapes[] = new Shape($node, $this->xpath);
            }
        }

        return $shapes;
    }
}
