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
        if ($s instanceof Media && $this->message_id != 0) {
            $params = $s->renderEdit($bot);
            $params['message_id'] = $this->message_id;
            $params['reply_markup'] = $this->keyboard->render();
            return [['editMessageMedia', $params]];
        }
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
            $s['message_id'] = $this->message_id;
            return [['editMessageText', $s]];

        } else {
            return [['send' . $sendType, $s]];
        }
    }
}
