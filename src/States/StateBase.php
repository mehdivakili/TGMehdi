<?php

namespace TGMehdi\States;

use TGMehdi\Facades\TGFacade;
use TGMehdi\Routing\BotRout;
use TGMehdi\TelegramBot;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\InlineMessage;

class StateBase
{

    use StateHelper;

    public string $state_key;
    public bool $is_state_change = false;


    public static $states = [];
    public $command_state;

    public bool $route_registered = false;

    public static function add_state(StateBase $state, $stay = true, $key = null)
    {
        $key = $key ?? $state->get_goto_key();
        self::$states[$key] = ['type' => 'state', 'state' => $state, 'stay' => $stay];
        $state->state_key = $key;
        if (!$state->is_state_change)
            $state->is_state_change = $stay;
    }

    public function get_goto_key()
    {
        return get_class($this);
    }

    public function __construct($key, $stay_active = false)
    {
        if (!is_null($key)) {
            $this->state = $key;
        }
        if (is_null($this->output_state)) {
            $this->output_state = $this->state;
        }
        if (!is_null($key)) {
            if (!str_starts_with($this->state, '.')) $this->state = '.' . $this->state;
            if (!str_ends_with($this->state, '.')) $this->state = $this->state . '.';
            if (!str_starts_with($this->output_state, '.')) $this->output_state = '.' . $this->output_state;
            if (!str_ends_with($this->output_state, '.')) $this->output_state = $this->output_state . '.';

        } else {
            $this->output_state = $this;
            $stay_active = false;
        }
        if (!is_null($key)) {
            self::add_state($this, $stay_active, $key);
        }
    }

    protected $state = null;
    protected $output_state = null;
    protected $regex = [];
    public TelegramBot $bot;

    protected $beforeEnter;
    protected $enterMiddleware;
    protected $afterEnter;
    protected $beforeExit;
    protected $exitMiddleware;
    protected $afterExit;

    protected $beforeStay;
    protected $stayMiddleware;
    protected $afterStay;

    public $keyboard;

    protected $default_text = "test";

    public static function add_abbr(string $key, string $state)
    {
        self::$states[$key] = ['type' => 'abbr', 'state' => $state];
    }

    function getDefaultText()
    {
        return __($this->default_text);
    }

    function setDefaultText(string $default_text)
    {
        $this->default_text = $default_text;
        return $this;
    }

    function setAfterEnter($afterEnter)
    {
        $this->afterEnter = $afterEnter;
        return $this;
    }

    function setEnterMiddleware($enterMiddleware)
    {
        $this->enterMiddleware = $enterMiddleware;
        return $this;

    }

    function setBeforeEnter($beforeEnter)
    {
        $this->beforeEnter = $beforeEnter;
        return $this;
    }

    function setExitMiddleware($exitMiddleware)
    {
        $this->exitMiddleware = $exitMiddleware;
        return $this;
    }

    function setBeforeExit($beforeExit)
    {
        $this->beforeExit = $beforeExit;
        return $this;
    }

    function setAfterExit($afterExit)
    {
        $this->afterExit = $afterExit;
        return $this;
    }

    function setBeforeStay($beforeStay)
    {
        $this->beforeStay = $beforeStay;
        return $this;
    }

    function setStayMiddleware($stayMiddleware)
    {
        $this->stayMiddleware = $stayMiddleware;
        return $this;

    }

    function setAfterStay($afterStay)
    {
        $this->afterStay = $afterStay;
        return $this;
    }


    function init(TelegramBot $bot)
    {
        $this->bot = $bot;
    }


    function getEnterState()
    {
        return $this->output_state;
    }

    function getState()
    {
        return $this->state;
    }

    function getRegexes()
    {
        return $this->regex;
    }

    function beforeEnter()
    {
        return $this->exec($this->beforeEnter);
    }

    function enterMiddleware()
    {
        if (is_callable($this->enterMiddleware))
            return $this->enterMiddleware();
        return $this->enterMiddleware;
    }

    function afterEnter()
    {
        if (!is_null($this->keyboard)) {
            if ($this->keyboard instanceof InlineKeyboard)
                return $this->exec(new InlineMessage($this->keyboard, $this->afterEnter));
            else {
                $this->bot->set_keyboard($this->keyboard);
                return $this->exec(['send',$this->afterEnter]);
            }
        }
        return $this->exec($this->afterEnter);


    }

    function beforeExit()
    {
        return $this->exec($this->beforeExit);

    }

    function exitMiddleware()
    {
        if (is_callable($this->exitMiddleware))
            return $this->exitMiddleware();
        return $this->exitMiddleware;

    }

    function afterExit()
    {
        return $this->exec($this->afterExit);

    }

    function beforeStay()
    {
        return $this->exec($this->beforeStay);

    }

    function stayMiddleware()
    {
        if (is_callable($this->stayMiddleware))
            return $this->stayMiddleware();
        return $this->stayMiddleware;
    }

    function afterStay()
    {
        return $this->exec($this->afterStay);

    }

    public function registerRoutes()
    {
        if (!$this->route_registered) {
            foreach ($this->getRegexes() as $type => $data) {
                foreach ($data as $regex => $args) {
                    $function = $args[0];
                    $f = (is_callable($function) or is_array($function)) ? $function : [$this, $function];
                    switch ($type) {
                        default:
                            BotRout::any($regex, $f);
                    }
                }
            }
            $this->route_registered = true;
        }
    }

    public function canExit()
    {
        return TGFacade::pass_middlewares($this->exitMiddleware());
    }

    public function canEnter()
    {
        return TGFacade::pass_middlewares($this->enterMiddleware());
    }

    public function canStay()
    {
        return TGFacade::pass_middlewares($this->stayMiddleware());
    }

    public function setKeyboard($keyboard)
    {
        $this->keyboard = $keyboard;
        return $this;
    }

    public function getCommandState()
    {
        if (!is_null($this->command_state)) {
            return $this->command_state;
        }
        return $this->getState();
    }

    public function setCommandState($state)
    {
        if (!str_starts_with($state, '.')) $state = '.' . $state;
        if (!str_ends_with($state, '.')) $state = $state . '.';

        $this->command_state = $state;
        return $this;
    }

}
