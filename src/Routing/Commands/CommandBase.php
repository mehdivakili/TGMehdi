<?php

namespace TGMehdi\Routing\Commands;

class CommandBase
{
    public static array $commands = [];
    public string $name;

    public function name($name)
    {
        $this->name = $name;
        self::$commands[$name] = $this;
    }
}