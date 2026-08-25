<?php

namespace TemplateInspector;

final class DirectiveParser
{
    private const DIRECTIVES = [
        'bind',
        'repeat',
        'rbind',
        'lookup',
        'asset',
        'image',
    ];

    public function parse(string $name): ?Directive
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        if ($name === 'ignore') {
            return new Directive(DirectiveType::Ignore, '');
        }

        foreach (self::DIRECTIVES as $keyword) {
            $prefix = $keyword . ':';

            if (str_starts_with($name, $prefix)) {
                $field = trim(substr($name, strlen($prefix)));

                return new Directive(DirectiveType::fromKeyword($keyword), $field);
            }
        }

        return null;
    }

    public function looksLikeDirective(string $name): bool
    {
        $name = trim($name);

        if ($name === '') {
            return false;
        }

        if ($name === 'ignore') {
            return true;
        }

        return str_contains($name, ':') && !str_contains($name, ' ');
    }
}
