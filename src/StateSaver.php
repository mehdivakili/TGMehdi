<?php

namespace TGMehdi;

class StateSaver
{
    public $states = [];
    public $results = [];

    public $commands = [];

    public function getStates(): array
    {
        return $this->states;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function setState($key, $state)
    {
        $this->states[$key] = $state;
    }

    public function pushResult($result)
    {
        $this->results[] = $result;
    }

    public function clearResult()
    {
        $this->results = [];
    }

    public function clearState()
    {
        $this->states = [];
    }

    public function setCommand($key, $command)
    {
        $this->commands[$key] = $command;
    }

    public function getCommands()
    {
        return $this->commands;
    }

    public function clearCommands()
    {
        $this->commands = [];
    }
}