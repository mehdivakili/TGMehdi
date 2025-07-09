<?php

namespace TGMehdi\States;

use TGMehdi\Routing\BotRout;
use TGMehdi\TelegramBot;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\ReplyKeyboard;

class InlineChoiceState extends StateBase
{
    protected $choices;
    protected $success_choice;
    protected $key;
    protected $state = ".";
    protected $output_state = ".same.";

    public string $name = "";
    protected $afterSuccess;
    protected $keyboardOrder = [-1];

    public bool $is_state_change = true;
    private string|null $message_state = null;

    public function name($name)
    {
        $this->name = $name;
        return $this;
    }


    public function setKeyboardOrder($o)
    {
        $this->keyboardOrder = $o;
        return $this;
    }

    public function registerRoutes()
    {
        $choices = array_values($this->getChoices());
        foreach ($choices as $key => $choice) {
            $choices[$key] = str_replace('/', '\/', str_replace('*', '\*', str_replace('+', '\+', $choice)));
        }
        $r = '/^(' . implode('|', $choices) . ')$/';
        $name = ($this->name) ?: $this->state_key;
        BotRout::message_callback("$r", [$this, "handle"], $this->getMessageState(), $this->getCommandState())->set_state_class($this)->name($name);
        parent::registerRoutes();
    }

    public function beforeEnter()
    {
        if (!$this->keyboard) {
            $keyboard = new InlineKeyboard();
            $choices = $this->getChoices();
            $choice_keys = array_keys($choices);
            $orderI = 0;
            $choiceI = 0;
            $order = $this->keyboardOrder[$orderI];
            $rowI = abs($order) - 1;
            while ($choiceI < count($choice_keys)) {
                $keyboard->newButton($choice_keys[$choiceI], $choices[$choice_keys[$choiceI++]], true);
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

    public function getChoices()
    {
        if (!isset($this->bot)) $this->init(app(TelegramBot::class));
        if ($this->choices and !is_array($this->choices) and is_callable($this->choices)) {
            $this->choices = call_with_dependency_inversion($this->bot, $this->choices, [], $this);
        }
        if (!is_array($this->choices)) $this->choices = [];
        if (array_key_first($this->choices) == 0) {
            $n = [];
            foreach ($this->choices as $choice) {
                $n[$choice] = $choice;
            }
        }
        return $this->choices;
    }

    public function success($choice)
    {
        $key = $this->getKey();
        if ($key) {
            $this->bot->message_temp($key, $choice);
        }
        $this->success_choice = $choice;
        $this->exec($this->afterSuccess, ['choice' => $choice]);
    }


    public function handle($input)
    {
        $choices = $this->getChoices();
        if (!$choices or in_array($input, $choices)) {
            $this->success($input);
        }
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
        return $this;
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

    public function setChoices($choices)
    {
        $this->choices = $choices;
        return $this;
    }

    public function setAfterSuccess($afterSuccess)
    {
        $this->afterSuccess = $afterSuccess;
        return $this;
    }

    public function setAfterNotfound($afterNotfound)
    {
        $this->afterNotfound = $afterNotfound;
        return $this;
    }

}
