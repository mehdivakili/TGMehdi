<?php
if (!function_exists('convertEnglishToPersian')) {
    function convertEnglishToPersian($string)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $output = str_replace($persian, $english, $string);
        return $output;
    }
}
if (!function_exists('showDate')) {
    function showDate($date, $type = null)
    {
        if (function_exists("verta")) {
            return match ($type) {
                null => verta($date),
                'date' => verta($date)->format("Y/m/d"),
                'time' => verta($date)->format("H:i"),
                'datetime' => verta($date)->format("Y/m/d H:i"),
                'expressive' => verta($date)->formatWord("l d S F"),
            };
        } else {
            return match ($type) {
                null, 'expressive' => $date,
                'date' => $date->format("Y-m-d"),
                'time' => $date->format("H:i"),
                'datetime' => $date->format("Y-m-d H:i"),
            };
        }
    }
}
