<?php

namespace TGMehdi\Facades;

use Illuminate;

class TGRout extends Illuminate\Support\Facades\Facade
{

    public static function getFacadeAccessor()
    {
        return \TGMehdi\Routing\TGRout::class;
    }
}