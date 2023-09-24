<?php

namespace App\Helpers;

class ArrayHelper
{
    public static function uniqueValues(array $values): array
    {
        $uniqueArray = array_unique($values);

        // Remove keys
        return array_values($uniqueArray);
    }
}
