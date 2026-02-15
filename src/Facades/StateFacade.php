<?php

namespace TGMehdi\Facades;

use Illuminate;
use TGMehdi\StateSaver;

class StateFacade extends Illuminate\Support\Facades\Facade
{

    public static function getFacadeAccessor()
    {
        return StateSaver::class;
    }
}