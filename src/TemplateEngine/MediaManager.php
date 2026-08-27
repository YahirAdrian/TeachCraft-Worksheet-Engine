<?php

namespace TemplateEngine;

use DOMDocument;
use DOMElement;
use RuntimeException;

/**
 * Manages media parts, slide-local relationships, and content types inside an
 * OpenXML package while the Template Processor replaces picture images.
 */
final class MediaManager
{
    private const CONTENT_TYPES_PATH = '[Content_Types].xml';
    private const IMAGE_RELATIONSHIP_TYPE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';

    private array $addedMedia = [];
    private array $slideRelationships = [];
    private bool $svgContentTypeEnsured = false;

    public function __construct(private OpenXMLPackage $package)
    {
    }

    /**
     * Add a media part to the package, skipping duplicates.
     *
     * @param string $mediaFile The ppt/media/... file name
     * @param string $contents The file contents
     */
    public function addMedia(string $mediaFile, string $contents): void
    {
        if (isset($this->addedMedia[$mediaFile])) {
            return;
        }

        $this->package->addPart("ppt/media/{$mediaFile}", $contents);
        $this->addedMedia[$mediaFile] = true;
    }

    /**
     * Ensure the package declares the SVG content type.
     */
    public function ensureSvgContentType(): void
    {
        if ($this->svgContentTypeEnsured) {
            return;
        }

        $contents = $this->package->getPart(self::CONTENT_TYPES_PATH);

        $document = new DOMDocument();
        $document->loadXML($contents);

        $defaults = $document->getElementsByTagName('Default');

        foreach ($defaults as $default) {
            if ($default instanceof DOMElement && $default->getAttribute('Extension') === 'svg') {
                $this->svgContentTypeEnsured = true;

                return;
            }
        }

        $extension = $document->createElement('Default');
        $extension->setAttribute('Extension', 'svg');
        $extension->setAttribute('ContentType', 'image/svg+xml');
        $document->documentElement->appendChild($extension);

        $this->package->replacePart(self::CONTENT_TYPES_PATH, $document->saveXML());
        $this->svgContentTypeEnsured = true;
    }

    /**
     * Ensure a slide has a relationship pointing to the given media file.
     *
     * @param int $slideNumber The slide number
     * @param string $mediaFile The ppt/media/... file name
     * @return string The relationship ID to reference from the picture
     */
    public function ensureRelationship(int $slideNumber, string $mediaFile): string
    {
        $target = "../media/{$mediaFile}";

        $state = $this->slideRelationships[$slideNumber] ?? $this->loadRelationships($slideNumber);

        if (isset($state['byTarget'][$target])) {
            $this->slideRelationships[$slideNumber] = $state;

            return $state['byTarget'][$target];
        }

        $nextId = $state['nextId'];
        $relationshipId = 'rId' . $nextId;

        $document = $state['document'];
        $relationship = $document->createElement('Relationship');
        $relationship->setAttribute('Id', $relationshipId);
        $relationship->setAttribute('Type', self::IMAGE_RELATIONSHIP_TYPE);
        $relationship->setAttribute('Target', $target);
        $document->documentElement->appendChild($relationship);

        $this->package->replacePart($this->relsPath($slideNumber), $document->saveXML());

        $state['byTarget'][$target] = $relationshipId;
        $state['nextId'] = $nextId + 1;
        $this->slideRelationships[$slideNumber] = $state;

        return $relationshipId;
    }

    private function loadRelationships(int $slideNumber): array
    {
        $path = $this->relsPath($slideNumber);

        $document = new DOMDocument();
        $document->loadXML($this->package->getPart($path));

        $byTarget = [];
        $maxId = 0;

        foreach ($document->getElementsByTagName('Relationship') as $relationship) {
            if (!$relationship instanceof DOMElement) {
                continue;
            }

            $id = $relationship->getAttribute('Id');
            $target = $relationship->getAttribute('Target');

            $byTarget[$target] = $id;

            if (str_starts_with($id, 'rId')) {
                $maxId = max($maxId, (int) substr($id, 3));
            }
        }

        return [
            'document' => $document,
            'byTarget' => $byTarget,
            'nextId' => $maxId + 1,
        ];
    }

    private function relsPath(int $slideNumber): string
    {
        return "ppt/slides/_rels/slide{$slideNumber}.xml.rels";
    }
}