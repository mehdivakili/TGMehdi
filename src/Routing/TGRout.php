<?php

namespace TGMehdi\Routing;

use Illuminate\Support\Facades\Log;
use TGMehdi\Routing\Commands\CommandContract;
use TGMehdi\Routing\Inputs\CallbackInput;
use TGMehdi\States\StateBase;
use TGMehdi\TelegramBot;

class TGRout
{
    public $routes = [];
    public $keys = [];
    public $status = '.';
    private $allowed_chat_types = ['private'];
    private $is_calaculated = false;
    private $middleware = true;
    /**
     * @var bool
     */
    private $chat_type;
    private $state_class = null;

    private $priority = 1;
    private $real_status;

    private $allowed_updates = ['message'];

    private function concat($base, $name)
    {
        $res = '.' . $base . '.' . $name . '.';
        while (str_contains($res, '..')) $res = str_replace('..', '.', $res);
        return $res;


    }

    private function join_middlewares($base_middleware, $middleware)
    {
        if (!is_array($base_middleware)) $base_middleware = [$base_middleware];
        if (!is_array($middleware)) $middleware = [$middleware];
        $b = $base_middleware;
        $a = $middleware;
        return array_merge($a, $b);
    }

    private function get_options($options)
    {
        $options['status'] = (!isset($options['status']) or $options['status'] == 'default') ? $this->status : $options['status'];
        $options['allowed_updates'] = (!isset($options['allowed_updates']) or $options['allowed_updates'] == 'default') ? $this->allowed_updates : $options['allowed_updates'];
        $options['allowed_chat_types'] = (!isset($options['allowed_chat_types']) or $options['allowed_chat_types'] == 'default') ? $this->allowed_chat_types : $options['allowed_chat_types'];
        $options['middleware'] = (!isset($options['middleware']) or $options['middleware'] == 'default') ? $this->middleware : $this->join_middlewares($this->middleware, $options['middleware']);
        $options['state_class'] = (!isset($options['state_class'])) ? $this->state_class : $options['state_class'];
        $options['priority'] = (!isset($options['priority'])) ? $this->priority : $options['priority'];
        return $options;

    }

    private function set_defaults($options)
    {
        $this->status = (!isset($options['status'])) ? $this->status : $options['status'];
        $this->middleware = (!isset($options['middleware'])) ? $this->middleware : $options['middleware'];
        $this->allowed_updates = (!isset($options['allowed_updates'])) ? $this->allowed_updates : $options['allowed_updates'];
        $this->allowed_chat_types = (!isset($options['allowed_chat_types'])) ? $this->allowed_chat_types : $options['allowed_chat_types'];
        $this->state_class = (!isset($options['state_class'])) ? $this->state_class : $options['state_class'];
        $this->priority = (!isset($options['priority'])) ? $this->priority : $options['priority'];
    }

    private function make_available_route(...$keys)
    {
        $r = $this->routes;
        foreach ($keys as $key) {
            if (!isset($r[$key])) $r[$key] = [];
            $r = $r[$key];
        }
    }

    public function is_available_route(...$keys)
    {
        $r = $this->routes;
        foreach ($keys as $key) {
            if (!isset($r[$key])) return false;
            $r = $r[$key];
        }
        return true;
    }

    public function add_command(CommandContract $command, $status = 'default', $priority = 1, $state_class = null)
    {
        $options = $this->get_options(['status' => $status, 'priority' => $priority, 'state_class' => $state_class]);
        if (str_starts_with($this->real_status, $options['status'])) {
            foreach ($options['allowed_chat_types'] as $allowed_chat_type) {
                $this->make_available_route($allowed_chat_type, $priority, $options['status']);
                $command->set_state($options['state_class']);
                $this->routes[$allowed_chat_type][$priority][$options['status']][] = $command;
            }
        }
        return $command;
    }

    public function group($options, $callback)
    {

        $defaults = $this->get_options([]);

        $options = $this->get_options($options);

        if (!$this->chat_type or in_array($this->chat_type, $options['allowed_chat_types'])) {
            $this->set_defaults($options);
            $callback();
            $this->set_defaults($defaults);
        }
    }

    public function state(StateBase $state)
    {
        $command_state = $state->getCommandState();
        $this->group(['status' => $command_state, 'state_class' => $state], function () use ($state) {
            $bot = app(TelegramBot::class);
            $state->init($bot);
            $state->registerRoutes();
        });
    }

    public function get_routes($bot_name, $chat_type, $real_status)
    {
        if (!$this->is_calaculated) {
            $this->chat_type = $chat_type;
            $this->real_status = $real_status;
            require(base_path("routes/bots/$bot_name.php"));
            $this->is_calaculated = true;
        }
        if (isset($this->routes[$chat_type])) return $this->routes;
        return [$chat_type => []];
    }

    public function state_abbr(string $key, string $state)
    {
        StateBase::add_abbr($key, $state);
    }

}