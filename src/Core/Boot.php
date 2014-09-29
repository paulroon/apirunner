<?php

namespace HappyCode\Core;

use Symfony\Component\Yaml\Parser;

class Boot {

    public static function Load($pathToConfig = "."){
        $fullPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $pathToConfig;
        $parser = new Parser();
        $raw_contents = file_get_contents($fullPath);
        return $parser->parse($raw_contents);
    }
} 