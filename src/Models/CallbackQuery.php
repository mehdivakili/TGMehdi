<?php

namespace TGMehdi\Models;

use Illuminate\Database\Eloquent\Model;
use TGMehdi\Facades\ChatFacade;

class CallbackQuery extends Model
{

    public function chat()
    {
        return $this->belongsTo(ChatFacade::getFacadeAccessor());
    }
}
