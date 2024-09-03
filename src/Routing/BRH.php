<?php

namespace TGMehdi\Routing;

use TGMehdi\States\SimpleState;

class BRH
{
    public static function add_goto($regex, $func, $state = null, $key = null)
    {
        $state = $state ?? BotRout::$status;
        BotRout::state((new SimpleState($state, add_to_states: (bool)$key, key: $key))
            ->setRegex($regex)
            ->setFunc($func)
        );
    }

    public static function command($command)
    {
        return '/^' . str_replace('/', '\/', $command) . '$/';
    }
}
