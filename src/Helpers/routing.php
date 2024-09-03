<?php
if (!function_exists("get_pos_regex")) {
    function get_pos_regex()
    {
        return '/^([1-9][0-9]*)$/';
    }
}
if (!function_exists("get_cost_regex")) {
    function get_cost_regex()
    {
        return '/^(([1-9][0-9]*|0)(\.[0-9]+)?)$/';
    }
}