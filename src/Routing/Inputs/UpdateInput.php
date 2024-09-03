<?php

namespace TGMehdi\Routing\Inputs;


use TGMehdi\TelegramBot;

class UpdateInput implements InputContract
{
    use InputHelper;

    private TelegramBot $bot;

    public function update_type()
    {
        if (isset($this->bot)) {
            return $this->bot->get_update_type();
        }
        return 'all';
    }

    public function parse_input(TelegramBot $bot)
    {
        $this->bot = $bot;

    }

    public function get_extracted_data()
    {
        return [];
    }

    public function chat_data()
    {
        if (isset($this->bot->data[$this->update_type()]['chat'])) {
            return $this->bot->data[$this->update_type()]['chat'];
        } else if (isset($this->bot->data['chat'])) {
            return $this->bot->data['chat'];
        } else {
            return $this->bot->data[$this->update_type()]['message']['chat'];
        }
    }
}