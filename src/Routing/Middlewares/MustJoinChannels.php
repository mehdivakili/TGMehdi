<?php


namespace TGMehdi\Routing\Middlewares;


use Illuminate\Support\Facades\Redis;
use TGMehdi\TelegramBot;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\InlineMessage;

class MustJoinChannels implements MiddlewareContract
{
    private array $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }

    public function handle(TelegramBot $bot)
    {
        $args = $this->args;
        $cache_status = Redis::get($bot->chat_id . '.join.' . $args[0]);
        if ($cache_status !== true) {
            $status = $bot->get_chat_member($args[0], $bot->data[$bot->get_update_type()]['from']["id"]);
            if (!isset($status["result"]["status"])) return true;
            $status = $status["result"]["status"];
            if ($status == "left" or $status == "kicked") {
                $keyboard = new InlineKeyboard();
                $keyboard->newButton($args[0], options: ['url' => "https://t.me/" . substr($args[0], 1)]);
                $bot->send_text(new InlineMessage($keyboard, __("tgmehdi.messages.you_must_join_channels")));
                return false;
            } else {
                Redis::set($bot->chat_id . '.join.' . $args[0], true, 86400);
            }
        }

        return true;
    }
}
