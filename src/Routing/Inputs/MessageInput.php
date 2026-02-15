<?php

namespace TGMehdi\Routing\Inputs;

use TGMehdi\Facades\BotRout;
use TGMehdi\TelegramBot;

class MessageInput implements InputContract
{
    use InputHelper;

    private mixed $message;
    private $data;
    private $message_type;
    /**
     * @var mixed|string
     */
    private $text;
    private $chat;

    public function update_type()
    {
        return 'message';
    }

    public function get_message_type()
    {
        $out = false;
        $types = BotRout::getTypes();
        foreach ($types as $type) {
            if (isset($this->message[$type])) {
                $out = $type;
            }
        }
        return $out;
    }

    public function set_chat($bot)
    {



    }

    public function parse_input(TelegramBot $bot)
    {
        $this->chat = $bot->data[$this->update_type()]['chat'];
        $this->message = $bot->data[$this->update_type()];
        $this->message_type = $this->get_message_type();

        $this->message_id = $bot->data[$this->update_type()]["message_id"];
        if (isset($this->message['text'])) {
            $this->text = $this->message['text'];
        } elseif (isset($this->message['caption'])) {
            $this->text = $this->message['caption'];
        } else {
            $this->text = '';
        }
        $this->text = convertEnglishToPersian($this->text);
    }

    public function get_extracted_data()
    {
        return ['text' => $this->text, 'type' => $this->get_message_type()];
    }

    public function chat_data()
    {
        return $this->chat;
    }

}