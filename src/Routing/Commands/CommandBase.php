<?php

namespace TGMehdi\Routing\Commands;

class CommandBase
{
    public string $name;

    public function name($name)
    {
        $this->name = $name;
    }
}