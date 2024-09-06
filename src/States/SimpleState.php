<?php

namespace TGMehdi\States;

class SimpleState extends StateBase
{
    protected $regex = null;

    protected $func;
    protected $commands = null;


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

    public function getRegexes()
    {
        if ($this->regex) {
            return [
                'any' => [
                    $this->regex => ['handle']
                ]
            ];
        } else {
            return [];
        }
    }

    public function handle()
    {
        $this->exec($this->func);
    }

    public function registerRoutes()
    {
        parent::registerRoutes();
        if ($this->commands) {
            general_call($this->bot, $this->commands, [], $this);
        }
    }
}
