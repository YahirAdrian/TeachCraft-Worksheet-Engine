<?php

namespace TemplateInspector;

final class Directive
{
    public function __construct(
        private DirectiveType $type,
        private string $field,
    ) {
    }

    public function getType(): DirectiveType
    {
        return $this->type;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
