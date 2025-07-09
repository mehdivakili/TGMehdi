<?php

namespace TGMehdi\States;

use TGMehdi\Routing\BotRout;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\ReplyKeyboard;

class InlineMuxState2 extends StateBase
{
    protected $commands = [];

    protected $filter = null;

    private string|null $message_state = null;
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
            $keyboard = new InlineKeyboard();
            $commands = $this->commands;
            $orderI = 0;
            $choiceI = 0;
            $order = $this->keyboardOrder[$orderI];
            $rowI = abs($order) - 1;
            while ($choiceI < count($commands)) {
                if (!is_null($this->filter)) {
                    $value = $this->exec(['return', $this->filter], ['command' => $commands[$choiceI++]]);
                    if ($value) {
                        $keyboard->newButton($value[0], $value[1], true);
                    }
                } else {
                    $keyboard->newButton($commands[$choiceI][0], $commands[$choiceI++][1], true);
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
        $this->bot->change_message_status($this->getMessageState());
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
            BotRout::message_callback($command[1], $command[2], $this->getMessageState(), $this->getCommandState())->set_state_class($this);
        }
        parent::registerRoutes();
    }

    public function setMessageState($message_state)
    {
        $this->message_state = $message_state;
    }

    public function getMessageState()
    {
        if (is_null($this->message_state)) {
            return $this->getState();
        }
        return $this->message_state;
    }
}