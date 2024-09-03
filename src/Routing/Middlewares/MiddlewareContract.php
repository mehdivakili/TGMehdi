<?php

namespace TGMehdi\Routing\Middlewares;

use TGMehdi\Routing\Inputs\InputContract;
use TGMehdi\TelegramBot;

interface MiddlewareContract
{

    public function handle(TelegramBot $bot);
}