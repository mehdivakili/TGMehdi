<?php

namespace TGMehdi\States;

use TGMehdi\Routing\BotRout;

class CallbackState extends StateBase
{
    protected $state = ".";
    protected $output_state = "same";

    protected $regex = [];

    public function registerRoutes()
    {
        $regexes = $this->getRegexes();
        foreach ($regexes as $regex => $action) {
            BotRout::callback($regex, $action, $this->getState());
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
