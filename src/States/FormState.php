<?php

namespace TGMehdi\States;

use TGMehdi\Facades\BotRout;

class FormState extends StateBase
{
    protected $regex = "/^(.*)$/";

    protected $key = "";

    public string $name = "";
    protected $filter;
    protected $success;
    public bool $is_state_change = true;
    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }
    public function registerRoutes()
    {
        $name = ($this->name) ?: $this->state_key;
        BotRout::any($this->regex,[$this,'handle'],$this->getCommandState())->name($name);
    }

    public function filter($input)
    {
        return $this->exec($this->filter, ['input' => $input]);
    }

    public function success($input)
    {
        $key = $this->getKey();
        if ($key) {
            $this->bot->temp($key, $input);
        }
        return $this->exec($this->success, ['input' => $input]);
    }

    public function handle($payload)
    {
        $res = $this->exec([$this, 'filter'], ['input' => $payload]);
        if ($res) {
            $this->success($payload);
        }
    }

    function setRegex($regex)
    {
        $this->regex = $regex;
        return $this;
    }

    function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    function setSuccess($success)
    {
        $this->success = $success;
        return $this;
    }

    public function getKey()
    {
        return $this->key;
    }

}
