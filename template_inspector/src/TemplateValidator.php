<?php

namespace TemplateInspector;

/**
 * Collects non-fatal warnings discovered while inspecting a template so the
 * caller can surface issues without aborting the inspection.
 */
final class TemplateValidator
{
    private array $warnings = [];

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}
