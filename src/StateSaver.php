<?php

namespace TGMehdi;

class StateSaver
{
    public $states = [];
    public $results = [];

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
}