<?php

namespace TGMehdi\Routing\Commands;

use TGMehdi\TelegramBot;

trait CommandHelper
{
    public $bot = null;
    public $state = null;
    public $func = null;
    public $middleware = null;

    public function set_tg(TelegramBot $bot)
    {
        $this->bot = $bot;
        return $this;
    }

    public function set_state($state)
    {
        $this->state = $state;
        return $this;
    }

    public function func($func)
    {
        $this->func = $func;
        return $this;
    }

    public function middleware($middleware)
    {
        if (!isset($this->middleware)) {
            $this->middleware = [];
        }
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }

    public function can_execute()
    {
        if ($this->bot->pass_middlewares($this->middleware) and $this->is_matched($this->bot->input)) return true;
        return false;
    }

    public function execute()
    {
        return general_call($this->bot, $this->func, $this->get_extracted_args(), $this->state);
    }

}