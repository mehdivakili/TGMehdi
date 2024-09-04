<?php

namespace TGMehdi\Listeners;

use Illuminate\Support\Facades\Context;

class LogRedisCommand
{
    public function handle($command)
    {
        Context::push('redis-command', $command);
    }

}