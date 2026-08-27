<?php

namespace TemplateEngine;

/**
 * Resolves emoji lookup values into OpenMoji SVG assets.
 *
 * OpenMoji stores its SVGs as files named after the emoji's Unicode code
 * point(s) in uppercase hexadecimal, joined with '-' for multi-code-point
 * sequences (e.g. 2615.svg, 1F9D1-200D-1F373.svg).
 */
final class OpenMojiResolver implements AssetResolverInterface
{
    private const HEX_SEQUENCE = '/^[0-9A-Fa-f]+(?:[-_ ][0-9A-Fa-f]+)*$/';

    public function __construct(private string $libraryPath)
    {
    }

    public function resolve(string $value): ?string
    {
        $sequence = $this->normalize($value);

        foreach ($this->candidates($sequence) as $candidate) {
            $path = rtrim($this->libraryPath, '/') . '/' . $candidate . '.svg';

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = strtoupper(trim($value));

        if (str_starts_with($value, 'U+')) {
            $value = substr($value, 2);
        }

        if (preg_match(self::HEX_SEQUENCE, $value)) {
            return str_replace(['_', ' '], '-', $value);
        }

        return $this->toCodePointSequence($value);
    }

    private function toCodePointSequence(string $value): string
    {
        $characters = function_exists('mb_str_split')
            ? mb_str_split($value)
            : str_split($value);

        $codePoints = [];

        foreach ($characters as $character) {
            $codePoint = function_exists('mb_ord')
                ? mb_ord($character, 'UTF-8')
                : null;

            if ($codePoint === null || $codePoint === false) {
                continue;
            }

            $codePoints[] = strtoupper(dechex($codePoint));
        }

        return implode('-', $codePoints);
    }

    /**
     * Build lookup candidates that tolerate FE0F (variation selector) usage.
     *
     * @param string $sequence The normalized code point sequence
     * @return string[] Candidate file base names
     */
    private function candidates(string $sequence): array
    {
        $candidates = [$sequence];

        if (str_ends_with($sequence, '-FE0F')) {
            $candidates[] = substr($sequence, 0, -5);
        } else {
            $candidates[] = $sequence . '-FE0F';
        }

        return $candidates;
    }
}