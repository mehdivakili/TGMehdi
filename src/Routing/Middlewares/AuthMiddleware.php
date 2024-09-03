<?php

namespace TGMehdi\Routing\Middlewares;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use TGMehdi\Routing\Middlewares\MiddlewareContract;
use TGMehdi\TelegramBot;

class AuthMiddleware implements MiddlewareContract
{

    public function handle(TelegramBot $bot)
    {
        if (empty($bot->bot['cache_optimization']))
            return !empty($bot->chat()->user_id);
        return boolval($bot->chat_data('user_id'));
    }
}