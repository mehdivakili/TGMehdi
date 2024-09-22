<?php

namespace TGMehdi\States;

use TGMehdi\Routing\BotRout;

class SimpleState extends StateBase
{
    protected $regex = null;

    protected $func;
    protected $commands = null;
    public $name = "";

    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    function setRegex($regex)
    {
        $this->regex = $regex;
        return $this;
    }

    function setFunc($func)
    {
        $this->func = $func;
        return $this;
    }

    function commands($command)
    {
        $this->commands = $command;
        return $this;
    }


    public function handle()
    {
        $this->exec($this->func);
    }

    public function registerRoutes()
    {
        if ($this->regex) {
            $name = ($this->name) ?: $this->state_key;
            BotRout::any($this->regex, [$this, 'handle'], $this->getCommandState())->name($name);
        } else {
            return [];
        }
        if ($this->commands) {
            general_call($this->bot, $this->commands, [], $this);
        }
    }
}
