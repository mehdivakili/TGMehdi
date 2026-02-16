<?php


namespace TGMehdi\Routing;


use TGMehdi\BotKernel;
use TGMehdi\Routing\Commands\AlwaysCommand;
use TGMehdi\Routing\Commands\NamedCommand;
use TGMehdi\Routing\Commands\RegexCommand;
use TGMehdi\Routing\Commands\SimpleCommand;
use TGMehdi\Routing\Middlewares\AnyMiddleware;
use TGMehdi\Routing\Middlewares\ExceptMiddleware;
use TGMehdi\Routing\Middlewares\MessageStateMiddleware;
use TGMehdi\Routing\Middlewares\OnlyMiddleware;
use TGMehdi\States\StateBase;
use TGMehdi\Facades\TGRout;

class BotRout
{


    /**
     * @var array
     */
    public $routes = [];
    public $status = '.';

    public $message_status = '.';
    private $allowed_updates = ['message'];
    private $allowed_chat_types = ['private'];
    public $types = ['text', 'animation', 'audio', 'document', 'photo', 'sticker', 'video', 'video_note', 'voice', 'contact', 'dice', 'game', 'poll', 'venue', 'location'];
    private $middleware = true;
    private $state_class = null;

    private $priority = 1;


    /**
     *
     * @param $regex
     * @param $action
     */

    public function concat($base, $name)
    {
        $res = '.' . $base . '.' . $name . '.';
        while (str_contains($res, '..')) $res = str_replace('..', '.', $res);
        return $res;


    }

    private function guess_command($input)
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
        $options['status'] = (!isset($options['status']) or $options['status'] == 'default') ? $this->status : $this->concat($this->status, $options['status']);
        $options['message_status'] = (!isset($options['message_status']) or $options['message_status'] == 'default') ? $this->message_status : $this->concat($this->message_status, $options['message_status']);
        $options['middleware'] = (!isset($options['middleware']) or $options['middleware'] == 'default') ? $this->middleware : $this->join_middlewares($this->middleware, $options['middleware']);
        $options['allowed_updates'] = (!isset($options['allowed_updates']) or $options['allowed_updates'] == 'default') ? $this->allowed_updates : $options['allowed_updates'];
        $options['allowed_chat_types'] = (!isset($options['allowed_chat_types']) or $options['allowed_chat_types'] == 'default') ? $this->allowed_chat_types : $options['allowed_chat_types'];
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

    public function any($regex, $action, $status = 'default', $allowed_updates = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = $this->guess_command($regex);
        $options = $this->get_options(['status' => $status, 'allowed_updates' => $allowed_updates, 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new AnyMiddleware($options['allowed_updates'])))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public function default($action, $status = 'default', $allowed_updates = 'default', $middleware = 'default', $state_class = null, $priority = 0)
    {
        $options = $this->get_options(['status' => $status, 'allowed_updates' => $allowed_updates, 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        $command = (new AlwaysCommand())
            ->func($action)
            ->middleware((new AnyMiddleware($options['allowed_updates'])))
            ->middleware($options['middleware']);
        TGRout::add_command($command
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public function update($updates, $action, $status = 'default', $middleware = 'default')
    {
        $options = $this->get_options(['status' => $status, 'allowed_updates' => $updates, 'middleware' => $middleware]);
        $command = (new AlwaysCommand())
            ->func($action)
            ->middleware((new AnyMiddleware($options['allowed_updates'])))
            ->middleware($options['middleware']);
        TGRout::add_command($command
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public function group($options, $callback)
    {
        TGRout::group($options, $callback);
    }

    public function only($types, $regex, $action, $status = 'default', $allowed_updates = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = $this->guess_command($regex);
        $options = $this->get_options(['status' => $status, 'allowed_updates' => $allowed_updates, 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new OnlyMiddleware($options['allowed_updates'], $types)))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public function except($types, $regex, $action, $status = 'default', $allowed_updates = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = $this->guess_command($regex);
        $options = $this->get_options(['status' => $status, 'allowed_updates' => $allowed_updates, 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new ExceptMiddleware($options['allowed_updates'], $types)))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public function callback($regex, $action, $status = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = $this->guess_command($regex);
        $options = $this->get_options(['status' => $status, 'allowed_updates' => ['callback_query'], 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new AnyMiddleware($options['allowed_updates'])))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public function message_callback($regex, $action, $message_status = 'default', $status = 'default', $middleware = 'default', $state_class = null, $priority = 1)
    {
        $command = $this->guess_command($regex);
        $options = $this->get_options(['status' => $status, 'message_status' => $message_status, 'allowed_updates' => ['callback_query'], 'middleware' => $middleware, 'state_class' => $state_class, 'priority' => $priority]);
        TGRout::add_command($command->func($action)
            ->middleware((new AnyMiddleware($options['allowed_updates'])))
            ->middleware((new MessageStateMiddleware($options['message_status'])))
            ->middleware($options['middleware'])
            , $options['status'], $options['priority'], $options['state_class']);
        return $command;
    }

    public function state(StateBase $state)
    {
        TGRout::state($state);
    }

    public function on($key, $after_enter_func = null, $after_exit_func = null, $before_enter_func = null, $before_exit_func = null, $default_text = "test", $keyboard = null, $is_state = false)
    {
        if ($is_state) {
            $state = $key;
        } else {
            $state = 'same';
        }
        $this->state((new StateBase($key))
            ->setAfterEnter($after_enter_func)
            ->setBeforeEnter($before_enter_func)
            ->setBeforeExit($before_exit_func)
            ->setAfterExit($after_exit_func)
            ->setKeyboard($keyboard)
        );
    }

    public function state_abbr(string $key, string $state)
    {
        TGRout::state_abbr($key, $state);
    }

    public function registerMiddleware($name, $middleware)
    {
        BotKernel::$middlewares[$name] = $middleware;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getTypes()
    {
        return $this->types;
    }
}
