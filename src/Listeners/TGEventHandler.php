<?php

namespace TGMehdi\Listeners;

use Illuminate\Bus\Dispatcher;
use Illuminate\Support\Facades\Redis;
use TGMehdi\Events\Routing\AfterUpdateType;
use TGMehdi\Events\Routing\BeforeDataReceived;
use TGMehdi\Events\Telegram\RequestIsDone;
use TGMehdi\Events\Telegram\TGFinished;
use TGMehdi\Types\ReplyKeyboard;

class TGEventHandler
{
    public function data_init(BeforeDataReceived $event)
    {
        $bot = $event->bot;
        $bot->data = $event->data;
        $bot->update_type = $bot->get_update_type();
        if (!$bot->update_type) {
            die(200);
        }
        AfterUpdateType::dispatch($bot, $bot->update_type);

    }

    public function save_chat_state(TGFinished $event)
    {

        if (empty($event->bot['cache_optimization'])) {
            $event->bot->chat()->status = $event->bot->chat_status;
            $event->bot->chat->save();
        } else {
            if ($event->bot->chat_status != ".same." or $event->bot->chat_status != $event->bot->original_chat_status) {
                if (isset($event->bot->chat_temp) and empty($event->bot->chat_temp)) {
                    Redis::unlink("{$event->bot['name']}_chat_{$event->bot->chat_id}.temp");
                } else if (isset($event->bot->chat_temp)) {
                    Redis::hmset("{$event->bot['name']}_chat_{$event->bot->chat_id}.temp", $event->bot->temp());
                }
                $orig_status = $event->bot->chat_data('original_status');
                $s = $event->bot->chat_status;
                if (empty($orig_status)) {
                    $chat = $event->bot->chat();
                    if ($chat->status != $s) {
                        $chat->status = $s;
                        $chat->save();
                    }
                }
                if ($event->bot->chat_data_changed)
                    Redis::hmset("{$event->bot['name']}_chat_{$event->bot->chat_id}.data", $event->bot->chat_data());
            }
        }

    }

    public function send_delayed_message(RequestIsDone $event)
    {
        $tg = $event->bot;
        if (!$tg->send_reply('', [])
            and $tg->keyboard
            and ($tg->keyboard instanceof ReplyKeyboard)) {
            if ($tg->state_class and $tg->state_class->getDefaultText() != "test")
                $tg->send_text($tg->state_class->getDefaultText(), true);
        }

    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            RequestIsDone::class => 'send_delayed_message',
            TGFinished::class => 'save_chat_state',
            BeforeDataReceived::class => 'data_init',
        ];
    }


}