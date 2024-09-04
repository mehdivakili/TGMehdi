<?php

namespace TGMehdi\TGTraits;

use Illuminate\Support\Facades\Redis;
use TGMehdi\Events\Data\BeforeChatData;
use TGMehdi\Events\Data\ChatDataChanged;
use TGMehdi\Events\Routing\SetStateChased;
use TGMehdi\Events\States\AfterStay;
use TGMehdi\Events\States\BeforeStay;
use TGMehdi\Events\States\CanStay;
use TGMehdi\States\StateBase;

trait SetData
{
    public $data;
    public $chat_id;
    public array $keys = [];
    public $chat_type;
    public $chat_status;
    public $original_chat_status;
    private array $chat_data;
    public bool $chat_data_changed = false;
    private bool $chat_temp_delete = false;
    public StateBase|null $state_class = null;

    public function setKey($key, $value = false)
    {
        $this->keys[$key] = $value;
    }

    public function getKey($key)
    {
        if (isset($this->keys[$key])) {
            return $this->keys[$key];
        }
        return null;
    }

    public function chat_data($key = null, $value = null)
    {
        BeforeChatData::dispatch($this, $key, $value);
        if (!isset($this->chat_data))
            $this->chat_data = Redis::hgetall("{$this->bot['name']}_chat_{$this->chat_id}.data");
        if (is_null($this->chat_data))
            $this->chat_data = [];
        if (!is_null($value)) {
            ChatDataChanged::dispatch($this, $key, $value, $this->chat_data[$key] ?? null);
            $this->chat_data[$key] = $value;
            $this->chat_data_changed = true;
        }
        if (is_null($key))
            return $this->chat_data;
        if (isset($this->chat_data[$key]))
            return $this->chat_data[$key];
        return null;
    }

    public function set_state(mixed $real_status)
    {
        $states = explode($real_status, '.');
        for ($i = 1; $i < count($states); $i++)
            $states[$i] = '.' . $states[$i - 1] . '.' . $states[$i];
        $states[0] = '.';
        array_pop($states);
        foreach ($states as $state) {
            BeforeStay::dispatch($this, $state);
            if (CanStay::dispatch($this, $state)) {
                AfterStay::dispatch($this, $state);
            } else {
                return false;
            }
        }
        return true;
    }

    public function change_key($key = 0)
    {
        if (!empty($this->state_class)) {
            $this->state_class->beforeExit();
            if (!$this->state_class->canExit()) {
                return false;
            }
        }
        if ($key instanceof StateBase) {
            $key->beforeEnter();
            if ($key->canEnter()) {
                $this->state_class?->afterExit();
                $key->afterEnter();
                $this->state_class = $key;
            } else {
                return false;
            }
            $key = $key->getEnterState();
        }
        if (!str_starts_with($key, '.')) $key = '.' . $key;
        if (!str_ends_with($key, '.')) $key = $key . '.';
        if ($key != '.same.') {
            $this->chat_status = $key;
            $this->chat_data('status', $key);
            $this->set_state($key);
        }
        return true;
    }

    public function temp($key = null, $text = null)
    {
        if (empty($this->bot['cache_optimization']) and $this->bot['cache_optimization'] == false) {
            if ($text === null) {
                $d = json_decode($this->chat->temp_text, true);
                if (in_array($key, array_keys($d))) {
                    return $d[$key];
                }
                return "";
            }
            $data = json_decode($this->chat->temp_text, true);
            $data[$key] = $text;
            $this->chat->temp_text = json_encode($data);
            $this->chat->save();
            return $text;
        } else {
            if (!isset($this->chat_temp))
                $this->chat_temp = Redis::hgetall("{$this->bot['name']}_chat_{$this->chat_id}.temp");
            if (!is_null($text))
                $this->chat_temp[$key] = $text;
            if (is_null($key))
                return $this->chat_temp;
            if (isset($this->chat_temp[$key]))
                return $this->chat_temp[$key];
            return null;
        }
    }

    public function del_temp($key = null)
    {
        if (!is_null($key)) {
            unset($this->chat_temp[$key]);
        } else {
            $this->chat_temp = [];
        }
    }

}