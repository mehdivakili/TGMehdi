<?php

namespace TGMehdi\Events\Telegram;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TGMehdi\TelegramBot;

class TGInitialized
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TelegramBot $bot)
    {
    }
}