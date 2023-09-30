<?php

namespace App\Helpers;

class StringHelper
{
    public static function separateWordsByCapital(string $string): string
    {
        return trim(preg_replace('/(?<!\ )[A-Z]/', ' $0', $string));
    }

    public static function extractModelName(string $string): string
    {
        if (preg_match('/\[(?P<modelName>.*?)\]/', $string, $match)) {
            $modelNameWithoutSymbols = $match['modelName'];
        } else {
            $modelNameWithoutSymbols = 'Data';
        }

        $modelNameArray = explode('\\', $modelNameWithoutSymbols);

        return array_pop($modelNameArray);
    }
}
