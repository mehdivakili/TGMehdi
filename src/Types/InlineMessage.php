<?php

namespace TGMehdi\Types;

use Illuminate\Support\Str;
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
        $sendType = "Message";
        if ($s and !($s instanceof InlineMessage)) {
            if (is_array($s) and !isset($s['reply_markup'])) {
                $s['reply_markup'] = $this->keyboard->render();
            } else if ($s instanceof Media) {
                $sendType = Str::title($s->type);
                $s = $s->render($bot);
                $s['reply_markup'] = $this->keyboard->render();
            } else if (is_string($s)) {
                $s = ['text' => $s, 'reply_markup' => $this->keyboard->render()];
            }
        }
        if ($this->message_id != 0) {
            if ($sendType == 'Message')
                return [['editMessageText', $s]];
            else
                return [['editMessageCaption', $s]];

        } else {
            return [['send' . $sendType, $s]];
        }
    }
}