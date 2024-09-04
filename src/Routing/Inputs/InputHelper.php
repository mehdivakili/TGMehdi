<?php

namespace TGMehdi\Routing\Inputs;

use TGMehdi\Events\Data\DataParsed;
use TGMehdi\TelegramBot;

trait InputHelper
{
    public $message_id = null;

    public function handle(TelegramBot $bot)
    {
        if ($bot->update_type == $this->update_type()) {
            $bot->input = $this;
            $this->parse_input($bot);
            DataParsed::dispatch($this, $this);
            return false;
        }
    }

}