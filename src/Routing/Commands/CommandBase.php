<?php

namespace TGMehdi\Routing\Commands;

use TGMehdi\Facades\StateFacade;

class CommandBase
{
    public string $name;

    public function name($name)
    {
        $this->name = $name;
        StateFacade::setCommand($name, $this);
    }
}