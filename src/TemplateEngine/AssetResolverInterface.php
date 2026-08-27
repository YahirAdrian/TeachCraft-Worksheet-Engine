<?php

namespace TemplateEngine;

/**
 * Resolves a lookup content value into a local media asset file.
 */
interface AssetResolverInterface
{
    /**
     * Resolve a lookup value into the path of a local asset file.
     *
     * @param string $value The lookup content value
     * @return string|null The absolute path of the asset, or null if not found
     */
    public function resolve(string $value): ?string;
}