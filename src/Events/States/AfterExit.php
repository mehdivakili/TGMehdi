<?php

namespace TGMehdi\Events\States;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TGMehdi\TelegramBot;

class AfterExit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TelegramBot $tg, public string $state)
    {
    }
}