<?php

namespace TGMehdi\Types;

use TGMehdi\TelegramBot;

class Media
{
    public $file_id = null;
    public $type = null;
    public $caption = null;
    public $path;

    private function __construct($file_id, $type, $caption, $path)
    {
        $this->file_id = $file_id;
        $this->type = $type;
        $this->caption = $caption;
        $this->path = $path;
    }

    public static function withFileID($file_id, $type, $caption = null)
    {
        return new Media($file_id, $type, $caption, null);
    }

    public static function withPath($path, $type, $caption = null)
    {
        return new Media(null, $type, $caption, $path);
    }

    public static function withVideoFileID($file_id, $caption = null)
    {
        return self::withFileID($file_id, 'video', $caption);
    }

    public static function withVideoPath($path, $caption)
    {
        return self::withPath($path, 'video', $caption);
    }

    public static function withImageFileID($file_id, $caption = null)
    {
        return self::withFileID($file_id, 'photo', $caption);
    }

    public static function withImagePath($path, $caption = null)
    {
        return self::withPath($path, 'photo', $caption);
    }

    public static function withAudioFileID($file_id, $caption = null)
    {
        return self::withFileID($file_id, 'audio', $caption);
    }

    public static function withAudioPath($path, $caption = null)
    {
        return self::withPath($path, 'audio', $caption);
    }

    public static function withDocumentFileID($file_id, $caption = null)
    {
        return self::withFileID($file_id, 'document', $caption);
    }

    public static function withDocumentPath($path, $caption = null)
    {
        return self::withPath($path, 'document', $caption);
    }

    public function render(TelegramBot $bot)
    {
        $res = [];
        if ($this->file_id)
            $res[$this->type] = $this->file_id;
        elseif ($this->path)
            $res[$this->type] = new TelegramFile($this->path);
        if ($this->caption)
            $res['caption'] = general_call($bot, $this->caption, message_status: 'return');
        else
            $res['caption'] = $this->caption;
        return $res;
    }
}