<?php

namespace TemplateInspector;

use DOMElement;
use DOMXPath;

final class Shape
{
    public function __construct(private DOMElement $node, private DOMXPath $xpath)
    {
    }

    public function getNode(): DOMElement
    {
        return $this->node;
    }

    public function getName(): string
    {
        $nodes = $this->xpath->query(
            './p:nvSpPr/p:cNvPr'
            . ' | ./p:nvGrpSpPr/p:cNvPr'
            . ' | ./p:nvPicPr/p:cNvPr'
            . ' | ./p:nvGraphicFramePr/p:cNvPr'
            . ' | ./p:nvCxnSpPr/p:cNvPr',
            $this->node
        );

        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        $nameNode = $nodes->item(0);

        if (!$nameNode instanceof DOMElement) {
            return '';
        }

        return $nameNode->getAttribute('name');
    }

    public function isGroup(): bool
    {
        return $this->node->localName === 'grpSp';
    }

    public function isPicture(): bool
    {
        return $this->node->localName === 'pic';
    }

    public function getChildren(): array
    {
        if (!$this->isGroup()) {
            return [];
        }

        $nodes = $this->xpath->query(
            './p:sp | ./p:grpSp | ./p:pic | ./p:graphicFrame | ./p:cxnSp',
            $this->node
        );

        if ($nodes === false) {
            return [];
        }

        $children = [];

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $children[] = new Shape($node, $this->xpath);
            }
        }

        return $children;
    }
}
