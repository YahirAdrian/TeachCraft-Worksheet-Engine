<?php

namespace TemplateInspector;

enum DirectiveType: string
{
    case Bind = 'bind';
    case Repeat = 'repeat';
    case RBind = 'rbind';
    case Lookup = 'lookup';
    case Asset = 'asset';
    case Image = 'image';
    case Ignore = 'ignore';

    public function isAsset(): bool
    {
        return $this === self::Lookup || $this === self::Asset;
    }

    public static function fromKeyword(string $keyword): self
    {
        return match ($keyword) {
            'bind' => self::Bind,
            'repeat' => self::Repeat,
            'rbind' => self::RBind,
            'lookup' => self::Lookup,
            'asset' => self::Asset,
            'image' => self::Image,
            default => throw new \InvalidArgumentException(
                "Unknown directive keyword: {$keyword}"
            ),
        };
    }
}
