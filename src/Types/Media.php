<?php

namespace TGMehdi\Types;

use TGMehdi\TelegramBot;

class Media
{
    public $file_id = null;
    public $type = null;
    public $caption = null;
    public $path;
    public $data = null;

    private function __construct($file_id, $type, $caption, $path, $data = null)
    {
        $this->file_id = $file_id;
        $this->type = $type;
        $this->caption = $caption;
        $this->path = $path;
        $this->data = $data;
    }

    public static function withFileID($file_id, $type, $caption = null)
    {
        return new Media($file_id, $type, $caption, null);
    }

    public static function withPath($path, $type, $caption = null)
    {
        return new Media(null, $type, $caption, $path);
    }

    public static function withData($filename, $data, $type, $caption = null)
    {
        return new Media(null, $type, $caption, $filename, $data);
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

    public static function withDocumentData($filename, $data, $caption = null)
    {
        return self::withData($filename, $data, 'document', $caption);
    }

    public function render(TelegramBot $bot)
    {
        $res = [];
        if ($this->file_id)
            $res[$this->type] = $this->file_id;
        elseif ($this->path)
            $res[$this->type] = new TelegramFile($this->path, $this->data);
        if ($this->caption)
            $res['caption'] = general_call($bot, $this->caption, message_status: 'return');
        else
            $res['caption'] = $this->caption;
        return $res;
    }

    public function renderEdit(TelegramBot $bot)
    {
        if (!in_array($this->type, ['animation', 'audio', 'document', 'photo', 'video'], true)) {
            throw new \InvalidArgumentException("Media type '{$this->type}' cannot be edited with editMessageMedia.");
        }

        $rendered = $this->render($bot);
        if (!isset($rendered[$this->type])) {
            throw new \InvalidArgumentException('Editing media requires a file ID, URL, path, or file data.');
        }

        $file = $rendered[$this->type];
        $params = ['media' => [
            'type' => $this->type,
            'media' => $file instanceof TelegramFile ? 'attach://media_file' : $file,
            'caption' => $rendered['caption'],
        ]];
        $parseMode = config('tgmehdi.parse_mode');
        if ($parseMode !== null) {
            $params['media']['parse_mode'] = $parseMode;
        }
        if ($file instanceof TelegramFile) {
            // send_reply extracts top-level TelegramFile values for multipart uploads.
            $params['media_file'] = $file;
        }

        return $params;
    }
}
