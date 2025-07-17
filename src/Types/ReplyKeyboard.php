<?php


namespace TGMehdi\Types;


class ReplyKeyboard extends TelegramBaseKeyboard
{
    protected $keyboard = array(
        'keyboard' => array(
            array()
        )
    );
    public $is_sended = false;

    public function __construct($resize_keyboard = true, $one_time_keyboard = false, $selective = false, $persistent = true)
    {
        $this->keyboard['resize_keyboard'] = $resize_keyboard;
        $this->keyboard['one_time_keyboard'] = $one_time_keyboard;
        $this->keyboard['selective'] = $selective;
        $this->keyboard['persistent'] = $persistent;

    }

    public function newLine()
    {
        if (count($this->keyboard['keyboard'][count($this->keyboard['keyboard']) - 1]))
            $this->keyboard['keyboard'][] = array();
    }

    public function render()
    {
        $this->is_sended = true;
        if ($this->keyboard['keyboard'][count($this->keyboard['keyboard']) - 1] == []) {
            unset($this->keyboard['keyboard'][count($this->keyboard['keyboard']) - 1]);
        }
        return parent::render();
    }

    public function newButton($text, $options = [], $line = -1)
    {
        $line = ($line < 0) ? count($this->keyboard['keyboard']) - $line - 2 : $line;
        $options['text'] = $text;
        $this->keyboard['keyboard'][$line][] = $options;
    }
}
