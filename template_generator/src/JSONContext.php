<?php


namespace TemplateEngine;

use RuntimeException;

class JSONContext{

    public function __construct(private mixed $data){

    }

    public function getData(): mixed{
        return $this->data;
    }

    public function get(string $path): mixed{

        if ($path === '' || $path === '.') {
            return $this->data;
        }

        $segments = explode('.', $path);
        $value = $this->data;

        foreach($segments as $segment){
            if(!is_array($value)){
                return null;
            }

            if(!array_key_exists($segment, $value)){
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function getArray(string $path): array{
        $value = $this->get($path);

        if(!is_array($value)){
            throw new RuntimeException(
                "The repeat path '{$path}' is not an array"
            );
        }

        return $value;
    }
}