<?php

namespace TemplateEngine;

use Exception;
use ZipArchive;

class OpenXMLPackage{

    private ZipArchive $zip;

    public function __construct(string $pptxPath){
        $this->zip = new ZipArchive();

        if($this->zip->open($pptxPath) !== true){
            throw new Exception("Could not open the pptx file: $pptxPath");
        }
    }

    public function getSlideCount() : int{
        $count = 0;

        while ($this->zip->locateName("ppt/slides/slide" . ($count + 1) . ".xml") !== false) {
            $count++;
        }

        return $count;
    }

    public function hasSlide(int $number) : bool{
        $slidePath = "ppt/slides/slide{$number}.xml";

        return $this->zip->locateName($slidePath) !== false;
    }

    public function getSlidePath(int $number) : string{
        $slidePath = "ppt/slides/slide{$number}.xml";

        if(!$this->hasSlide($number)){
            throw new Exception("Slide $number does not exist");
        }

        return $slidePath;
    }

    public function getSlideXML(int $number) : string{
        $path = $this->getSlidePath($number);

        $xml = $this->zip->getFromName($path);

        if($xml === false){
            throw new Exception("Could not read {$path}");
        }

        return $xml;
    }

    public function replaceSlide(int $number, string $xml): void
    {
        $path = $this->getSlidePath($number);

        $result = $this->zip->addFromString($path, $xml);

        if($result === false){
            throw new Exception("Unable to replace {$path}");
        }
    }

    public function partExists(string $path) : bool{
        return $this->zip->locateName($path) !== false;
    }

    public function getPart(string $path) : string{
        $contents = $this->zip->getFromName($path);

        if($contents === false){
            throw new Exception("Could not read {$path}");
        }

        return $contents;
    }

    public function replacePart(string $path, string $contents): void
    {
        $result = $this->zip->addFromString($path, $contents);

        if($result === false){
            throw new Exception("Unable to replace {$path}");
        }
    }

    public function addPart(string $path, string $contents): void
    {
        $result = $this->zip->addFromString($path, $contents);

        if($result === false){
            throw new Exception("Unable to add {$path}");
        }
    }

    public function close() : void{
        $this->zip->close();
    }
}