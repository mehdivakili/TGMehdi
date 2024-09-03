<?php


namespace TGMehdi\Routing\Middlewares;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use TGMehdi\BotMiddleware;
use TGMehdi\TelegramBot;
use TGMehdi\Types\InlineKeyboard;

class MustJoinChannels implements MiddlewareContract
{
    private array $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }

    public function handle(TelegramBot $telegramBot)
    {
        $args = $this->args;
        $cache_status = Redis::get($telegramBot->chat_id . '.join.' . $args[0]);
        if ($cache_status !== true) {
            $status = $telegramBot->get_chat_member($args[0], $telegramBot->user["id"]);
            if (!isset($status["result"]["status"])) return true;
            $status = $status["result"]["status"];
            if ($status == "left" or $status == "kicked") {
                $keyboard = new InlineKeyboard();
                $keyboard->newButton($args[0], options: ['url' => "https://t.me/" . substr($args[0], 1)]);
                $telegramBot->set_keyboard($keyboard);
                $telegramBot->send_text(__("tgmehdi.messages.you_must_join_channels"));
                return false;
            } else {
                Redis::set($telegramBot->chat_id . '.join.' . $args[0], true, 86400);
            }
        }

        return true;
    }
}
