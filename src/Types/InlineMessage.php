<?php

namespace TGMehdi\Types;

use TGMehdi\TelegramBot;

class InlineMessage
{
    public $message_id = 0;

    public function __construct(protected InlineKeyboard $keyboard, protected $view)
    {
    }

    public function render(TelegramBot $bot)
    {
        $this->keyboard->state = $bot->m_state[$this->message_id] ?? null;
        $this->keyboard->temp = $bot->m_temp[$this->message_id] ?? null;
        $s = general_call($bot, $this->view, ['inline_keyboard' => $this->keyboard], null, 'return');
        if ($s and !($s instanceof InlineMessage)) {
            if (is_array($s) and !isset($s['reply_markup'])) {
                $s['reply_markup'] = $this->keyboard->render();
            } else if ($s instanceof Media) {
                $s = $s->render($bot);
                $s['reply_markup'] = $this->keyboard->render();
            } else if (is_string($s)) {
                $s = ['text' => $s, 'reply_markup' => $this->keyboard->render()];
            }
        }
        return $s;
    }
}