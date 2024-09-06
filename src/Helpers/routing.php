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

if (!function_exists("tg_route")) {
    function tg_route($name, $params = [])
    {
        $r = \TGMehdi\Routing\Commands\CommandBase::$commands[$name];
        if ($r instanceof \TGMehdi\Routing\Commands\NamedCommand) {
            return $r->generate_route($params);
        } else if ($r instanceof \TGMehdi\Routing\Commands\SimpleCommand) {
            return $r->command;
        } else {
            throw new Exception("named command not supported");
        }
    }
}