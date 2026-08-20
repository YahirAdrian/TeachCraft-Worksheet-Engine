<?php

namespace TemplateInspector;

use RuntimeException;
use ZipArchive;

final class OpenXMLPackage
{
    private ZipArchive $zip;

    public function __construct(private string $path)
    {
        $this->zip = new ZipArchive();

        if ($this->zip->open($this->path) !== true) {
            throw new RuntimeException(
                "Could not open the pptx file: {$this->path}"
            );
        }
    }

    public function getSlideCount(): int
    {
        $count = 0;

        while ($this->zip->locateName("ppt/slides/slide" . ($count + 1) . ".xml") !== false) {
            $count++;
        }

        return $count;
    }

    public function getSlideXML(int $number): string
    {
        $path = "ppt/slides/slide{$number}.xml";
        $xml = $this->zip->getFromName($path);

        if ($xml === false) {
            throw new RuntimeException("Could not read {$path}");
        }

        return $xml;
    }

    public function close(): void
    {
        $this->zip->close();
    }
}
