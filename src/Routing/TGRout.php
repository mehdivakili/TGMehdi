<?php

namespace TGMehdi\Routing;

use TGMehdi\Routing\Commands\CommandContract;
use TGMehdi\Routing\Inputs\CallbackInput;
use TGMehdi\States\StateBase;
use TGMehdi\TelegramBot;

class TGRout
{
    public static $routes = [];
    public static $keys = [];
    public static $status = '.';
    private static $allowed_chat_types = ['private'];
    private static $is_calaculated = false;
    /**
     * @var bool
     */
    private static $chat_type;
    private static $state_class = null;

    private static $priority = 1;
    private static $real_status;

    private static function concat($base, $name)
    {
        $res = '.' . $base . '.' . $name . '.';
        while (str_contains($res, '..')) $res = str_replace('..', '.', $res);
        return $res;


    }

    private static function join_middlewares($base_middleware, $middleware)
    {
        if (!is_array($base_middleware)) $base_middleware = [$base_middleware];
        if (!is_array($middleware)) $middleware = [$middleware];
        $b = $base_middleware;
        $a = $middleware;
        return array_merge($a, $b);
    }

    private static function get_options($options)
    {
        $options['status'] = (!isset($options['status']) or $options['status'] == 'default') ? self::$status : $options['status'];
        $options['allowed_chat_types'] = (!isset($options['allowed_chat_types']) or $options['allowed_chat_types'] == 'default') ? self::$allowed_chat_types : $options['allowed_chat_types'];
        $options['state_class'] = (!isset($options['state_class'])) ? self::$state_class : $options['state_class'];
        $options['priority'] = (!isset($options['priority'])) ? self::$priority : $options['priority'];
        return $options;

    }

    private static function set_defaults($options)
    {
        self::$status = (!isset($options['status'])) ? self::$status : $options['status'];
        self::$allowed_chat_types = (!isset($options['allowed_chat_types'])) ? self::$allowed_chat_types : $options['allowed_chat_types'];
        self::$state_class = (!isset($options['state_class'])) ? self::$state_class : $options['state_class'];
        self::$priority = (!isset($options['priority'])) ? self::$priority : $options['priority'];
    }

    private static function make_available_route(...$keys)
    {
        $r = self::$routes;
        foreach ($keys as $key) {
            if (!isset($r[$key])) $r[$key] = [];
            $r = $r[$key];
        }
    }

    public static function is_available_route(...$keys)
    {
        $r = self::$routes;
        foreach ($keys as $key) {
            if (!isset($r[$key])) return false;
            $r = $r[$key];
        }
        return true;
    }

    public static function add_command(CommandContract $command, $status = 'default', $priority = 1, $state_class = null)
    {
        $options = self::get_options(['status' => $status, 'priority' => $priority, 'state_class' => $state_class]);
        if (str_starts_with(self::$real_status, $options['status'])) {
            foreach ($options['allowed_chat_types'] as $allowed_chat_type) {
                self::make_available_route($allowed_chat_type, $priority, $options['status']);
                $command->set_state($options['state_class']);
                self::$routes[$allowed_chat_type][$priority][$options['status']][] = $command;
            }
        }
        return $command;
    }

    public static function group($options, $callback)
    {

        $defaults = self::get_options([]);

        $options = self::get_options($options);

        if (!self::$chat_type or in_array(self::$chat_type, $options['allowed_chat_types'])) {
            self::set_defaults($options);
            $callback();
            self::set_defaults($defaults);
        }
    }

    public static function state(StateBase $state)
    {
        $command_state = $state->getCommandState();
        self::group(['status' => $command_state, 'state_class' => $state], function () use ($state) {
            $bot = app(TelegramBot::class);
            $state->init($bot);
            $state->registerRoutes();
        });
    }

    public static function get_routes($bot_name, $chat_type, $real_status)
    {
        if (!self::$is_calaculated) {
            self::$chat_type = $chat_type;
            self::$real_status = $real_status;
            include_once base_path("routes/bots/$bot_name.php");
            self::$is_calaculated = true;
        }
        if (isset(self::$routes[$chat_type])) return self::$routes;
        return [$chat_type => []];
    }

    public static function state_abbr(string $key, string $state)
    {
        StateBase::add_abbr($key, $state);
    }

}