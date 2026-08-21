<?php

require 'vendor/autoload.php';

use TemplateEngine\OpenXMLPackage;
use TemplateEngine\Slide;
use TemplateEngine\Shape;
use TemplateEngine\JSONContext;
use TemplateEngine\TemplateProcessor;

$template_folder = __DIR__ . '/templates';
$template_name = 'template.pptx';
$template_path = "{$template_folder}/{$template_name}";
$output_path = __DIR__ . '/output/generated.pptx';
$json_path = __DIR__ . '/data/content.json';



try{

    //Validate the existence of the template and JSON files, and create the output directory if it doesn't exist
    if (!is_file($template_path)) {
        throw new RuntimeException(
            "Template not found: {$template_path}"
        );
    }

    if (!is_file($json_path)) {
        throw new RuntimeException(
            "JSON file not found: {$json_path}"
        );
    }

    if (!is_dir(dirname($output_path))) {
        mkdir(dirname($output_path), 0775, true);
    }

    if (!copy($template_path, $output_path)) {
        throw new RuntimeException(
            'Unable to copy the PPTX template.'
        );
    }

    // Check if the JSON data file exists
    $jsonContents = file_get_contents($json_path);

    if($jsonContents === false) {
        throw new RuntimeException("Unable to read the JSON file: {$json_path}");
    }

    $content = json_decode($jsonContents, true, 512, JSON_THROW_ON_ERROR);


    // Load the template and process the first slide


    $package = new OpenXMLPackage($template_path);

    $xml = $package->getSlideXML(1);

    $slide = new Slide($xml);

    $context = new JSONContext($content);

    // Process the slide with the context using the template processor
    
    $processor = new TemplateProcessor($slide);

    $processor->process($context);
        
    $package->replaceSlide(1, $slide->getXml());
    $package->close();

}catch(Exception $e){
    echo "Error: " . $e->getMessage();
}
