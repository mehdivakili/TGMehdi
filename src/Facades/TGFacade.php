<?php

namespace TGMehdi\Facades;

use Illuminate\Support\Facades\Facade;
use TGMehdi\TelegramBot;

class TGFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TelegramBot::class;
    }

}
