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

    public function __serialize(): array
    {
        // Laravel embeds serialized jobs in JSON, which cannot contain raw binary data.
        return [
            'path' => $this->path,
            'name' => $this->name,
            'data_base64' => $this->data === null ? null : base64_encode($this->data),
        ];
    }

    public function __unserialize(array $values): void
    {
        $this->path = $values['path'];
        $this->name = $values['name'];
        // Accept jobs serialized before binary-safe serialization was introduced.
        $this->data = array_key_exists('data_base64', $values)
            ? ($values['data_base64'] === null ? null : base64_decode($values['data_base64'], true))
            : ($values['data'] ?? null);
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
