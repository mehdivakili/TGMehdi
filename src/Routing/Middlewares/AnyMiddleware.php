<?php

namespace TGMehdi\Routing\Middlewares;

use TGMehdi\Routing\Inputs\InputContract;
use TGMehdi\TelegramBot;

class AnyMiddleware implements MiddlewareContract
{
    public function __construct(private $update_types = [])
    {
    }

    public function handle(TelegramBot $bot)
    {
        if (in_array($bot->input->update_type(), $this->update_types)) {
            return true;
        } else {
            return false;
        }
    }
}