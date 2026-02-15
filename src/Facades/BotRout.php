<?php

namespace TGMehdi\Facades;

use Illuminate;

class BotRout extends Illuminate\Support\Facades\Facade
{

    public static function getFacadeAccessor()
    {
        return \TGMehdi\Routing\BotRout::class;
    }
}