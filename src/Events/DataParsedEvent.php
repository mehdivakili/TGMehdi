<?php

namespace TGMehdi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use TGMehdi\TelegramBot;

class DataParsedEvent
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public TelegramBot $telegram
    )
    {
    }
}