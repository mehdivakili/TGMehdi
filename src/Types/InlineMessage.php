<?php

namespace TGMehdi\Types;

use TGMehdi\TelegramBot;

class InlineMessage
{
    public function __construct(protected InlineKeyboard $keyboard, protected $view)
    {
    }

    public function render(TelegramBot $bot)
    {
        $s = general_call($bot, $this->view, ['inline_keyboard' => $this->keyboard], $this->view, 'return');
        if ($s and !($s instanceof InlineMessage)) {
            if (is_array($s) and !isset($s['reply_markup'])) {
                $s['reply_markup'] = $this->keyboard->render();
            } else if (is_string($s)) {
                $s = ['text' => $s, 'reply_markup' => $this->keyboard->render()];
            }
        }
        return $s;
    }
}