<?php

namespace TGMehdi\Types;

use Illuminate\Support\Facades\Storage;

class TelegramFile
{
    public $data;
    public $path;
    public $name;

    public function __construct($path, $data = null)
    {
        $this->path = $path;
        $this->data = $data;
        preg_match('/[\/\\\]?(.+)$/', $path, $matches);
        $this->name = $matches[1];
    }

    public function getFile()
    {
        if ($this->data) {
            return $this->data;
        } else {
            return Storage::get($this->path);
        }
    }
}
