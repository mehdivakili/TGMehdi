<?php

namespace TGMehdi\States;

use TGMehdi\Facades\BotRout;
use TGMehdi\Types\ReplyKeyboard;

class MuxState extends StateBase
{
    protected $commands = [];

    protected $filter = null;

    protected $keyboardOrder = [-1];

    public function addCommand(...$args)
    {
        $this->commands[] = $args;
        return $this;
    }

    public function setFilter(callable $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    public function beforeEnter()
    {
        if (!$this->keyboard) {
            $keyboard = new ReplyKeyboard();
            $commands = $this->commands;
            $orderI = 0;
            $choiceI = 0;
            $order = $this->keyboardOrder[$orderI];
            $rowI = abs($order) - 1;
            while ($choiceI < count($commands)) {
                if (!is_null($this->filter)) {
                    $value = $this->exec(['return', $this->filter], ['command' => $commands[$choiceI++]]);
                    if ($value) {
                        $keyboard->newButton($value[0]);
                    }
                } else {
                    $keyboard->newButton($commands[$choiceI++][0]);
                }
                if ($order == 0) {
                    break;
                }
                if ($rowI == 0) {
                    $keyboard->newLine();
                    if ($order > 0) {
                        $orderI++;
                        $order = $this->keyboardOrder[$orderI];
                    }
                    $rowI = abs($order) - 1;
                } else {
                    $rowI--;
                }

            }
            $this->setKeyboard($keyboard);

        }
        return parent::beforeEnter();
    }

    public function setKeyboardOrder($o)
    {
        $this->keyboardOrder = $o;
        return $this;
    }

    public function registerRoutes()
    {
        foreach ($this->commands as $command) {
            BotRout::any(...$command);
        }
        parent::registerRoutes();
    }
}