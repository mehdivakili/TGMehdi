<?php

namespace TGMehdi\Routing\Inputs;

use TGMehdi\TelegramBot;

class CallbackInput implements InputContract
{

    use InputHelper;

    private $chat;
    private $callback;
    private $text;

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


    }

    public function get_extracted_data()
    {
        return ['text' => $this->text];
    }

    public function chat_data()
    {
        return $this->chat;
    }

}