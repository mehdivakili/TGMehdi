<?php

namespace TGMehdi\Events\Routing;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TGMehdi\TelegramBot;

class AfterSetState
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TelegramBot $bot, public string $state)
    {
    }
}