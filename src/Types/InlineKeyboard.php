<?php


namespace TGMehdi\Types;


class InlineKeyboard extends TelegramBaseKeyboard
{
    protected $keyboard = array(
        'inline_keyboard' => array(
            array()
        )
    );

    public function newLine()
    {
        if (count($this->keyboard['inline_keyboard'][count($this->keyboard['inline_keyboard']) - 1]))
            $this->keyboard['inline_keyboard'][] = array();
    }

    public function newButton($text, $data = 'trash', $options = [], $line = -1)
    {
        $line = ($line < 0) ? count($this->keyboard['inline_keyboard']) - $line - 2 : $line;
        $options['text'] = $text;
        if (!isset($options['web_app']))
            $options['callback_data'] = $data;
        $this->keyboard['inline_keyboard'][$line][] = $options;
    }

}
