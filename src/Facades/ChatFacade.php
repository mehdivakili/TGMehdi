<?php

namespace TGMehdi\Facades;

use Illuminate;

class ChatFacade extends Illuminate\Support\Facades\Facade
{

    public static function getFacadeAccessor()
    {
        return config("tgmehdi.chat");
    }
}