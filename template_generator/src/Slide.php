<?php

namespace TemplateEngine;

use DomDocument;
use DomXPath;
use DOMElement;
use TemplateEngine\Shape;

class Slide{

    private DomDocument $dom;
    private DomXPath $xpath;
    private int $nextShapeId;

    public function __construct(string $xml){

        $this->document = new DomDocument();

        $this->document->loadXML($xml);

        $this->xpath = new DomXPath($this->document);

        $this->registerNamespaces();

        $this->nextShapeId  = $this->findMaximunShapeId() + 1;
    }

    private function registerNamespaces() : void{
        $this->xpath->registerNamespace(
            'p',
            'http://schemas.openxmlformats.org/presentationml/2006/main'
        );

        $this->xpath->registerNamespace(
            'a',
            'http://schemas.openxmlformats.org/drawingml/2006/main'
        );
    }

    public function getDocument(): DomDocument{
        return $this->document;
    }

    public function getXPath(): DomXPath{
        return $this->xpath;
    }

    public function getShapes(): array{
        
        $nodes = $this->xpath->query(
            '/p:sld/p:cSld/p:spTree/p:sp'
            . ' | /p:sld/p:cSld/p:spTree/p:grpSp'
            . ' | /p:sld/p:cSld/p:spTree/p:pic'
            . ' | /p:sld/p:cSld/p:spTree/p:graphicFrame'
        );
            
        if($nodes === false){
            return [];
        }
                
        $shapes = [];

        foreach($nodes as $node){
            $shapes[] = new Shape($node, $this->xpath);
        }

        return $shapes;
    }

    public function getXML() : string{
         $xml = $this->document->saveXML();

        if ($xml === false) {
            throw new \RuntimeException(
                'Unable to serialize slide XML.'
            );
        }

        return $xml;
    }

    private function findMaximunShapeId() : int{
        $nodes = $this->xpath->query('//p:cNvPr');

        if ($nodes === false){
            return 0;
        }

        $maximun = 0;

        foreach($nodes as $node){
            if(!$node instanceof DOMElement){
                continue;
            }

            $id = (int) $node->getAttribute('id');

            $maximun = max($maximun, $id);
        }

        return $maximun;
    }

    public function reassignShapeIds(DOMElement $element): void {
            
        $nodes = $this->xpath->query(
            './/p:cNvPr'
            . ' | ./p:nvSpPr/p:cNvPr'
            . ' | ./p:nvGrpSpPr/p:cNvPr',
            $element
        );

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $node->setAttribute(
                'id',
                (string) $this->nextShapeId
            );

            $this->nextShapeId++;
        }
    }
}