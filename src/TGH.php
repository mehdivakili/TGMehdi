<?php

namespace TGMehdi;

use TGMehdi\States\StateBase;

class TGH
{

    private static function general_call(TelegramBot $telegramBot, $func, $args)
    {
        return general_call($telegramBot, $func, $args);
    }

    public static function goto_stat($status, $func = null, $keyboard = null)
    {
        return function (TelegramBot $telegramBot, $args) use ($keyboard, $status, $func) {

            $telegramBot->set_keyboard($keyboard);
            self::general_call($telegramBot, $func, $args);
            $telegramBot->change_status($status);
        };
    }

    public static function goto_stat_if($true_stat, $false_stat, $func, $keyboard = null)
    {
        return function (TelegramBot $telegramBot, $args) use ($keyboard, $true_stat, $false_stat, $func) {
            $telegramBot->set_keyboard($keyboard);
            $res = self::general_call($telegramBot, $func, $args);
            $telegramBot->change_status(($res) ? $true_stat : $false_stat);
        };
    }

    public static function form($next_status, $key, $filter_func = null, $after_func = null)
    {
        return function (TelegramBot $telegramBot, $args) use ($next_status, $key, $filter_func, $after_func) {
            $res = self::general_call($telegramBot, $filter_func, $args[0]);
            if ($res) {

                $telegramBot->change_status($next_status);
                $telegramBot->temp($key, $args[0]);
                self::general_call($telegramBot, $after_func, $args);
            }

        };
    }

    public static function choice($next_status, $key, $choices = null, $not_found_func = null, $after_func = null)
    {
        return function (TelegramBot $telegramBot, $args) use ($not_found_func, $next_status, $key, $choices, $after_func) {
            if ($choices and is_callable($choices)) $choices = self::general_call($telegramBot, $choices, []);
            if (!$choices or in_array($args[0], array_keys($choices))) {
                $telegramBot->change_status($next_status);
                $telegramBot->temp($key, ($choices) ? $choices[$args[0]] : $args[0]);
                self::general_call($telegramBot, $after_func, [$choices[$args[0]]]);
            } else {
                self::general_call($telegramBot, $not_found_func, [$args[0]]);
            }


        };
    }

    public static function lang_to_regex($key, $args = [])
    {
        return "/^" . __($key, $args) . "$/";
    }

    public static function with_keyboard($function, $keyboard)
    {
        return function (TelegramBot $telegramBot, $args) use ($function, $keyboard) {
            $telegramBot->set_keyboard($keyboard);
            self::general_call($telegramBot, $function, $args);
        };
    }

    public static function exec($function)
    {
        return function (TelegramBot $telegramBot, $args) use ($function) {
            self::general_call($telegramBot, $function, $args);
        };
    }
}