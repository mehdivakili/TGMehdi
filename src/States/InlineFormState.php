<?php

namespace TGMehdi\States;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TGMehdi\Routing\BotRout;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\InlineMessage;

class InlineFormState extends StateBase
{
    protected $state = ".";
    protected $output_state = ".same.";

    protected $confirm_data = "confirm";
    protected $startKeyboard;
    protected $endKeyboard;
    protected $afterKeyboard;

    protected $formRegex = "/.*/";

    protected $success;
    private string|null $message_state = null;
    protected $keyboardLayout = [];

    protected $key = null;
    private $confirm_text = "confirm";
    private $delete_data = "delete";

    protected $filter;

    public function setMessageState($message_state)
    {
        $this->message_state = $message_state;
        return $this;
    }

    public function getMessageState()
    {
        if (is_null($this->message_state)) {
            return $this->getState();
        }
        return $this->message_state;
    }

    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }


    public function setKeyboardLayout($keyboardLayout)
    {
        $this->keyboardLayout = $keyboardLayout;
        return $this;
    }

    public function setStartKeyboard($func)
    {
        $this->startKeyboard = $func;
        return $this;
    }

    public function setTranslations($next, $page, $prev)
    {
        $this->next_text = $next;
        $this->prev_text = $prev;
        $this->page_text = $page . ' ';
        return $this;
    }

    public function setEndKeyboard($func)
    {
        $this->endKeyboard = $func;
        return $this;
    }

    public function setAfterKeyboard($func)
    {
        $this->afterKeyboard = $func;
        return $this;
    }

    public function startKeyboard()
    {
        $c = $this->startKeyboard;
        if (is_callable($c))
            $c($this);
    }

    public function endKeyboard()
    {
        $c = $this->endKeyboard;
        if (is_callable($c))
            $c($this);
    }

    public function beforeEnter()
    {
        $this->make_keyboard();
        $this->bot->change_message_status($this->getMessageState());
        $this->setKeyboard($this->keyboard);
        return parent::beforeEnter();
    }

    private function getDataRegex()
    {
        return "/^(" . implode("|", array_values(Arr::flatten($this->keyboardLayout))) . ")$/";
    }

    public function registerRoutes()
    {
        BotRout::message_callback($this->confirm_data, [$this, "handle"], $this->getMessageState(), $this->getCommandState())->set_state_class($this);
        BotRout::message_callback($this->delete_data, [$this, "delKeys"], $this->getMessageState(), $this->getCommandState())->set_state_class($this);
        BotRout::message_callback($this->getDataRegex(), [$this, "setKeys"], $this->getMessageState(), $this->getCommandState())->set_state_class($this);
        parent::registerRoutes();
    }

    public function make_keyboard()
    {
        $this->keyboard = new InlineKeyboard();
        $this->startKeyboard();
        $this->keyboard->newLine();
        $this->keyboard->newButton($this->bot->message_temp($this->key) ?? "");
        foreach ($this->keyboardLayout as $row) {
            $this->keyboard->newLine();
            foreach ($row as $text => $data) {
                $this->keyboard->newButton($text, $data, true);
            }

        }
        $this->keyboard->newLine();
        $this->endKeyboard();
    }

    public function updateState()
    {
        $this->make_keyboard();
        $this->keyboard->state = $this->bot->get_message_status();
        $this->keyboard->temp = $this->bot->message_temp();
        $this->bot->edit_message_keyboard($this->keyboard);
    }

    public function setKeys($character)
    {
        $this->bot->message_temp($this->key, $this->bot->message_temp($this->key) . $character);
        $this->updateState();

    }

    public function delKeys()
    {
        $this->bot->message_temp($this->key, substr($this->bot->message_temp($this->key), 0, -1));
        $this->updateState();
    }

    function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    function setRegex($regex)
    {
        $this->formRegex = $regex;
        return $this;
    }

    function setSuccess($success)
    {
        $this->success = $success;
        return $this;
    }

    public function filter($input)
    {
        return $this->exec($this->filter, ['input' => $input]);
    }

    public function handle()
    {
        $key = $this->key;
        $value = null;
        if ($key) {
            $value = $this->bot->message_temp($key);
        }
        if (preg_match($this->formRegex, $value)) {

            $res = $this->exec([$this, 'filter'], ['input' => $value]);
            if ($res) {
                $this->exec($this->success, ['value' => $value]);
            }
        }
    }
}