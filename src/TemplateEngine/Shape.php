<?php

namespace TemplateEngine;

use DOMElement;
use DOMXPath;

require 'vendor/autoload.php';


class Shape{
    public function __construct(private DOMElement $node, private DOMXPath $xpath){

    }

    public function getNode(): DOMElement{
        return $this->node;
    }

    public function getName() : string{


        $nodes = $this->xpath->query(
            './p:nvSpPr/p:cNvPr'
            . ' | ./p:nvGrpSpPr/p:cNvPr'
            . ' | ./p:nvPicPr/p:cNvPr'
            . ' | ./p:nvGraphicFramePr/p:cNvPr'
            . ' | ./p:nvCxnSpPr/p:cNvPr'
        , $this->node);

        if($nodes === false || $nodes->length === 0){
            return '';
        }


        $nameNode = $nodes->item(0);

        if(!$nameNode instanceof DOMElement){
            return '';
        }

        return $nameNode->getAttribute('name');
    }

    public function isGroup() : bool{
        return $this->node->localName === 'grpSp';
    }

    public function isPicture() : bool{
        return $this->node->localName === 'pic';
    }

    public function setImageReference(string $relationshipId) : void{

        if(!$this->isPicture()){
            return;
        }

        $blips = $this->xpath->query('.//a:blip', $this->node);

        if($blips === false || $blips->length === 0){
            return;
        }

        $blip = $blips->item(0);

        if(!$blip instanceof DOMElement){
            return;
        }

        $blip->setAttribute('r:embed', $relationshipId);
    }

    public function getChildren(): array{
        if(!$this->isGroup()){
            return [];
        }

        $nodes = $this->xpath->query(
            './p:sp | ./p:grpSp | ./p:pic | ./p:graphicFrame | ./p:cxnSp',
            $this->node
        );

        if($nodes === false){
            return [];
        }

        $children = [];

        foreach($nodes as $node){
            if($node instanceof DOMElement){
                $children[] = new Shape($node, $this->xpath);
            }
        }

        return $children;
    }

    public function getText() : string{

        if($this->isGroup()){
            return '';
        }

        $textNodes = $this->xpath->query('.//a:t', $this->node);

        if($textNodes->length === 0){
            return '';
        }

        $text = '';

        foreach($textNodes as $textNode){
            $text .= $textNode->nodeValue;
        }

        return $text;
    }

    public function setText(string $newText) : void{

        if($this->isGroup()){
            return;
        }

        $texts = $this->xpath->query('.//a:t', $this->node);

        if ($texts->length === 0) {
            return;
        }

        // Replace the first text node
        $texts->item(0)->nodeValue = $newText;

        // Remove any remaining text runs
        for ($i = 1; $i < $texts->length; $i++) {
            $texts->item($i)->nodeValue = '';
        }
    }

    
}