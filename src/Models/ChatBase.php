<?php

namespace TGMehdi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ReflectionFunction;

abstract class ChatBase extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function callbacks()
    {
        return $this->hasMany(CallbackQuery::class);
    }

    public function temp($key, $text = null)
    {
        if ($text === null) {
            $d = json_decode($this->temp_text, true);
            if (in_array($key, array_keys($d))) {
                return $d[$key];
            }
            return "";
        }
        $data = json_decode($this->temp_text, true);
        $data[$key] = $text;
        $this->temp_text = json_encode($data);
        $this->save();
        return $text;
    }

}
