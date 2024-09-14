<?php

namespace TGMehdi\States;

use TGMehdi\Routing\BotRout;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\ReplyKeyboard;

class InlineMuxState extends StateBase
{
    protected $commands = [];

    protected $keyboardOrder = [-1];

    public function addCommand(...$args)
    {
        $this->commands[] = $args;
        return $this;
    }

    public function beforeEnter()
    {
        if (!$this->keyboard) {
            $keyboard = new InlineKeyboard();
            $commands = $this->commands;
            $orderI = 0;
            $choiceI = 0;
            $order = $this->keyboardOrder[$orderI];
            $rowI = abs($order) - 1;
            while ($choiceI < count($commands)) {
                $keyboard->newButton($commands[$choiceI++][0], $commands[$choiceI++][1]);
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
            BotRout::callback(...(array_slice($command, 1)));
        }
        parent::registerRoutes();
    }
}