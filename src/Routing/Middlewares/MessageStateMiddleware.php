<?php

namespace TGMehdi\Routing\Middlewares;

use TGMehdi\Routing\Inputs\InputContract;
use TGMehdi\TelegramBot;

class MessageStateMiddleware implements MiddlewareContract
{

    public function __construct(private $state = ".")
    {
    }


    public function handle(TelegramBot $bot)
    {
        $data = $bot->input->get_extracted_data();

        if (isset($data['message']) and str_starts_with($data['message']['state'], $this->state)) {
            return true;
        }
        return false;
    }
}