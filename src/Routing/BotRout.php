<?php


namespace TGMehdi\Routing;


use Illuminate\Contracts\Http\Kernel;
use TGMehdi\BotKernel;
use TGMehdi\Routing\Commands\AlwaysCommand;
use TGMehdi\Routing\Commands\NamedCommand;
use TGMehdi\Routing\Commands\RegexCommand;
use TGMehdi\Routing\Commands\SimpleCommand;
use TGMehdi\Routing\Middlewares\AnyMiddleware;
use TGMehdi\Routing\Middlewares\ExceptMiddleware;
use TGMehdi\Routing\Middlewares\OnlyMiddleware;
use TGMehdi\States\StateBase;

class BotRout
{


    /**
     * @var array
     */
    public static $routes = [];
    public static $status = '.';
    private static $allowed_updates = ['message'];
    private static $allowed_chat_types = ['private'];
    public static $types = ['text', 'animation', 'audio', 'document', 'photo', 'sticker', 'video', 'video_note', 'voice', 'contact', 'dice', 'game', 'poll', 'venue', 'location'];
    private static $middleware = true;
    private static $chat_type;
    private static $state_class = null;

    private static $priority = 1;


    /**
     *
     * @param $regex
     * @param $action
     */

    private static function concat($base, $name)
    {
        $res = '.' . $base . '.' . $name . '.';
        while (str_contains($res, '..')) $res = str_replace('..', '.', $res);
        return $res;


    }

    private static function guess_command($input)
    {
        if (is_string($input) and str_starts_with($input, '/') and str_ends_with($input, '/')) {
            return new RegexCommand($input, []);
        } else if (is_array($input)) {
            return new RegexCommand($input[0], $input[1]);
        } else if (str_contains($input, "{{")) {
            return new NamedCommand($input);
        } else {
            return new SimpleCommand($input);
        }
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
        $options['status'] = (!isset($options['status']) or $options['status'] == 'default') ? self::$status : self::concat(self::$status, $options['status']);
        $options['middleware'] = (!isset($options['middleware']) or $options['middleware'] == 'default') ? self::$middleware : self::join_middlewares(self::$middleware, $options['middleware']);
        $options['allowed_updates'] = (!isset($options['allowed_updates']) or $options['allowed_updates'] == 'default') ? self::$allowed_updates : $options['allowed_updates'];
        $options['allowed_chat_types'] = (!isset($options['allowed_chat_types']) or $options['allowed_chat_types'] == 'default') ? self::$allowed_chat_types : $options['allowed_chat_types'];
        $options['state_class'] = (!isset($options['state_class'])) ? self::$state_class : $options['state_class'];
        $options['priority'] = (!isset($options['priority'])) ? self::$priority : $options['priority'];
        return $options;

    }

    private static function set_defaults($options)
    {
        self::$status = (!isset($options['status'])) ? self::$status : $options['status'];
        self::$middleware = (!isset($options['middleware'])) ? self::$middleware : $options['middleware'];
        self::$allowed_updates = (!isset($options['allowed_updates'])) ? self::$allowed_updates : $options['allowed_updates'];
        self::$allowed_chat_types = (!isset($options['allowed_chat_types'])) ? self::$allowed_chat_types : $options['allowed_chat_types'];
        self::$state_class = (!isset($options['state_class'])) ? self::$state_class : $options['state_class'];
        self::$priority = (!isset($options['priority'])) ? self::$priority : $options['priority'];
    }

    public static function any($regex, $action, $status = 'default', $allowed_updates = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = self::guess_command($regex);
        $options = self::get_options(['status' => $status, 'allowed_updates' => $allowed_updates, 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new AnyMiddleware($options['allowed_updates'])))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public static function default($action, $status = 'default', $allowed_updates = 'default', $middleware = 'default', $state_class = null, $priority = 0)
    {
        $options = self::get_options(['status' => $status, 'allowed_updates' => $allowed_updates, 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        $command = (new AlwaysCommand())
            ->func($action)
            ->middleware((new AnyMiddleware($options['allowed_updates'])))
            ->middleware($options['middleware']);
        TGRout::add_command($command
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public static function update($updates, $action, $status = 'default', $middleware = 'default')
    {
        $options = self::get_options(['status' => $status, 'allowed_updates' => $updates, 'middleware' => $middleware]);
        $command = (new AlwaysCommand())
            ->func($action)
            ->middleware((new AnyMiddleware($updates)))
            ->middleware($options['middleware']);
        TGRout::add_command($command
            , $options['status'], $options['priority'], $options['state_class']);
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

    public static function only($types, $regex, $action, $status = 'default', $allowed_updates = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = self::guess_command($regex);
        $options = self::get_options(['status' => $status, 'allowed_updates' => $allowed_updates, 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new OnlyMiddleware($options['allowed_updates'], $types)))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public static function except($types, $regex, $action, $status = 'default', $allowed_updates = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = self::guess_command($regex);
        $options = self::get_options(['status' => $status, 'allowed_updates' => $allowed_updates, 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new ExceptMiddleware($options['allowed_updates'], $types)))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public static function callback($regex, $action, $status = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = self::guess_command($regex);
        $options = self::get_options(['status' => $status, 'allowed_updates' => ['callback_query'], 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new AnyMiddleware($options['allowed_updates'])))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public static function state(StateBase $state)
    {
        TGRout::state($state);
    }

    public static function on($key, $after_enter_func = null, $after_exit_func = null, $before_enter_func = null, $before_exit_func = null, $default_text = "test", $keyboard = null, $is_state = false)
    {
        if ($is_state) {
            $state = $key;
        } else {
            $state = 'same';
        }
        self::state((new StateBase($key))
            ->setAfterEnter($after_enter_func)
            ->setBeforeEnter($before_enter_func)
            ->setBeforeExit($before_exit_func)
            ->setAfterExit($after_exit_func)
            ->setKeyboard($keyboard)
        );
    }

    public static function state_abbr(string $key, string $state)
    {
        TGRout::state_abbr($key, $state);
    }

    public static function registerMiddleware($name, $middleware)
    {
        BotKernel::$middlewares[$name] = $middleware;
    }

}
