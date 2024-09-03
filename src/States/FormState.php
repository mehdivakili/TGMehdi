<?php

namespace TGMehdi\States;

class FormState extends StateBase
{
    protected $regex = "/^(.*)$/";

    protected $key = "";

    protected $filter;
    protected $success;


    public function getRegexes()
    {
        return [
            'any' => [
                $this->regex => ['handle']
            ]
        ];
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
