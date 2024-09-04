<?php

namespace TGMehdi\Events\Command;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TGMehdi\Routing\Commands\CommandContract;
use TGMehdi\TelegramBot;

class BeforeCommandCheck
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TelegramBot $bot, public CommandContract $command)
    {

    }
}