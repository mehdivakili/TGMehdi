<?php

namespace TGMehdi\States;

use TGMehdi\Facades\BotRout;
use TGMehdi\Types\InlineMessage;

class CallbackState extends StateBase
{
    protected $state = ".";
    protected $output_state = "same";
    public $command_state = '.';
    public string $name = "";

    protected $regex = [];

    public function name($name)
    {
        $this->name = $name;
        return $this;
    }
    public function registerRoutes()
    {
        $regexes = $this->getRegexes();
        foreach ($regexes as $regex => $action) {
            $action = (is_null($this->keyboard)) ? $action : new InlineMessage($this->keyboard, $action);
            $name = ($this->name) ?: $this->state_key;
            BotRout::callback($regex, $action, $this->getCommandState())->set_state_class($this)->name($name);
        }
    }

    public function setRegexes(array $regexes)
    {
        $this->regex = $regexes;
        return $this;
    }

    public function setRegex($regex, $action)
    {
        $this->regex[$regex] = $action;
        return $this;
    }

}
