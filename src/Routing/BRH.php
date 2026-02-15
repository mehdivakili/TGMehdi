<?php

namespace TGMehdi\Routing;

use TGMehdi\States\SimpleState;

class BRH
{
    public static function add_goto($regex, $func, $key,$command_state )
    {
        $state = $state ?? BotRout::getStatus();
        BotRout::state((new SimpleState($key))
            ->setRegex($regex)
            ->setFunc($func)
            ->setCommandState($command_state)
        );
    }

    public static function command($command)
    {
        return '/^' . str_replace('/', '\/', $command) . '$/';
    }
}
