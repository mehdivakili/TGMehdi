<?php

namespace TGMehdi\States;

use TGMehdi\TelegramBot;
use TGMehdi\Types\ReplyKeyboard;

class ChoiceState extends StateBase
{
    protected $choices;
    protected $success_choice;
    protected $key;
    protected $afterSuccess;
    protected $keyboardOrder = [-1];

    public function setKeyboardOrder($o)
    {
        $this->keyboardOrder = $o;
        return $this;
    }

    public function getRegexes()
    {
        $choices = array_keys($this->getChoices());
        foreach ($choices as $key => $choice) {
            $choices[$key] = str_replace('/', '\/', str_replace('*', '\*', str_replace('+', '\+', $choice)));
        }
        $r = '/^(' . implode('|', $choices) . ')$/';
        return [
            'any' => [
                $r => ['handle']
            ]
        ];
    }

    public function beforeEnter()
    {
        if (!isset($this->keyboard)) {
            $keyboard = new ReplyKeyboard();
            $choices = $this->getChoices();
            $choice_keys = array_keys($choices);
            $orderI = 0;
            $choiceI = 0;
            $order = $this->keyboardOrder[$orderI];
            $rowI = abs($order) - 1;
            while ($choiceI < count($choice_keys)) {
                $keyboard->newButton($choice_keys[$choiceI++]);
                if ($order == 0) {
                    break;
                }
                if ($rowI == 0) {
                    $keyboard->newLine();
                    $rowI = abs($order) - 1;
                } else {
                    $rowI--;
                }
                if ($order > 0) {
                    $orderI++;
                    $order = $this->keyboardOrder[$orderI];
                }
            }
            $this->setKeyboard($keyboard);

        }
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
            $this->bot->temp($key, $choice);
        }
        $this->success_choice = $choice;
        $this->exec($this->afterSuccess, ['choice' => $choice]);
    }


    public function handle($input)
    {
        $choices = $this->getChoices();
        if (!$choices or in_array($input, array_keys($choices))) {
            $this->success(($choices) ? $choices[$input] : $input);
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
