<?php

namespace TGMehdi\Models;

use Illuminate\Database\Eloquent\Model;
use TGMehdi\Facades\ChatFacade;

class Message extends Model
{
    protected $guarded = [];

    public function chat()
    {
        return $this->belongsTo(ChatFacade::getFacadeAccessor());
    }

    public function message()
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }
}
