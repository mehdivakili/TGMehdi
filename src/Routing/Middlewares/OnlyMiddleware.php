<?php

namespace TGMehdi\Routing\Middlewares;

use TGMehdi\Routing\Inputs\InputContract;
use TGMehdi\TelegramBot;

class OnlyMiddleware implements MiddlewareContract
{

    public function __construct(private $update_types = [], private $types = [])
    {
    }


    public function handle(TelegramBot $bot)
    {
        $data = $bot->input->get_extracted_data();
        if (in_array($bot->input->update_type(), $this->update_types) and isset($data['type']) and in_array($data['type'], $this->types)) {
            return true;
        }
        return false;
    }
}