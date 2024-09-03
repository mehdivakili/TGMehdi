<?php

namespace TGMehdi\States;

class SimpleState extends StateBase
{
    protected $regex = "/^.*$/";

    protected $func;


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

    public function getRegexes()
    {
        return [
            'any' => [
                $this->regex => ['handle']
            ]
        ];
    }

    public function handle()
    {
        $this->exec($this->func);
    }
}
