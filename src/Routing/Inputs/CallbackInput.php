<?php

namespace TGMehdi\Routing\Inputs;

use TGMehdi\TelegramBot;

class CallbackInput implements InputContract
{

    use InputHelper;

    private $chat;
    private $callback;
    private $text;

    private $m_state = "";
    private $m_temp = [];

    private $m_text = null;

    public function update_type()
    {
        return 'callback_query';
    }

    public function parse_input(TelegramBot $bot)
    {
        $this->chat = $bot->data["callback_query"]["message"]["chat"];
        $this->callback = $bot->data["callback_query"];
        $this->text = $bot->data["callback_query"]["data"];
        $this->message_id = $bot->data["callback_query"]['message']["message_id"];
        if (str_starts_with($this->text, "-\n")) {
            $text = substr($this->text, 2);
            $data = explode("\n", $text);
            $this->m_text = $data[0];
            if (count($data) > 1)
                $this->m_state = '.' . $data[1] . '.';
            if (count($data) > 2)
                parse_str($data[2], $this->m_temp);
            $bot->set_message_state($this->text, $this->m_state, $this->m_temp,$this->message_id);
        }
    }

    public function get_extracted_data()
    {
        if (is_null($this->m_text))
            return ['text' => $this->text];
        else
            return ['text' => $this->m_text, 'message' => ['state' => $this->m_state, 'text' => $this->text, 'data' => $this->m_temp]];
    }

    public function chat_data()
    {
        return $this->chat;
    }

}