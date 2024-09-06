<?php

namespace TGMehdi\States;

use TGMehdi\Routing\BotRout;
use TGMehdi\Types\InlineMessage;

class CallbackState extends StateBase
{
    protected $state = ".";
    protected $output_state = "same";
    public $command_state = '.';

    protected $regex = [];

    public function registerRoutes()
    {
        $regexes = $this->getRegexes();
        foreach ($regexes as $regex => $action) {
            $action = (is_null($this->keyboard)) ? $action : new InlineMessage($this->keyboard, $action);
            BotRout::callback($regex, $action, $this->getCommandState())->set_state_class($this);
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
