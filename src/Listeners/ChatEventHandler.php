<?php

namespace TGMehdi\Listeners;

use Illuminate\Events\Dispatcher;
use TGMehdi\Events\Data\ChatDataParsed;
use TGMehdi\Events\Data\ChatDataReceived;
use TGMehdi\Events\Data\ChatNotCreated;
use TGMehdi\Events\Data\DataParsed;
use TGMehdi\Facades\ChatFacade;

class ChatEventHandler
{
    private function chat_find_or_create(ChatNotCreated $event)
    {
        $bot = $event->bot;
        $chat = ChatFacade::where('chat_id', $bot->chat_id)->where('bot_name', $bot->bot['name'])->first();
        if (empty($chat)) {
            $chat = app("tgmehdi.chat");
            $chat->chat_id = $bot->chat_id;
            $chat->bot_name = $bot->bot['name'];
            $chat->type = $bot->chat_type;
            $chat->status = '.start.';
            $chat->temp_text = '{}';
            if (in_array($chat->type, $bot->bot['allowed_chats'])) {
                $chat->save();
            }
        }
        $bot->chat = $chat;
        $bot->chat_status = $bot->chat->status;
        $bot->original_chat_status = $bot->chat->status;
        $bot->chat_data('status', $bot->chat_status);
        $bot->chat_data('original_status', $bot->original_chat_status);
    }

    public function set_chat_data(DataParsed $event)
    {
        $chat_data = $event->bot->chat_data();
        $bot = $event->bot;
        ChatDataReceived::dispatch($this, $chat_data);
        $bot->chat_id = $chat_data['id'];
        $bot->chat_type = $chat_data['type'];
        if (empty($bot->bot['cache_optimization'])) {
            ChatNotCreated::dispatch($this, $chat_data);
        } else {
            $bot->chat_data();
            $bot->chat_status = $bot->chat_data('status');
            $bot->original_chat_status = $bot->chat_data('original_status');
            if (!$bot->original_chat_status)
                ChatNotCreated::dispatch($this, $chat_data);
        }
        ChatDataParsed::dispatch($this);
    }

    public function subscribe(Dispatcher $events)
    {
        return [
            ChatNotCreated::class => 'chat_find_or_create',
            DataParsed::class => 'set_chat_data',
        ];
    }
}