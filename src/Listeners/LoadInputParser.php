<?php

namespace TGMehdi\Listeners;

use TGMehdi\BotKernel;
use TGMehdi\Events\Routing\AfterUpdateType;

class LoadInputParser
{
    public function handle(AfterUpdateType $event): void
    {
        foreach (BotKernel::$input_parsers as $inputParser) {
            call_user_func([$inputParser, 'handle'], $event->bot);
        }
    }
}