<?php

namespace TGMehdi\States;

use TGMehdi\Facades\TGFacade;
use TGMehdi\Routing\BotRout;
use TGMehdi\TelegramBot;

class StateBase
{

    use StateHelper;


    public static $states = [];

    public static function add_state(StateBase $state, $stay = true, $key = null)
    {
        $key = $key ?? $state->get_goto_key();
        self::$states[$key] = ['type' => 'state', 'state' => $state, 'stay' => $stay];
    }

    public function get_goto_key()
    {
        return get_class($this);
    }

    public function __construct($state = null, $output_state = null, $add_to_states = true, $key = null, $stay_active = false)
    {
        if (!is_null($state)) {
            $this->state = $state;
        }
        if (!is_null($output_state)) {
            $this->output_state = $output_state;
        }
        if (is_null($this->output_state)) {
            $this->output_state = $this->state;
        }
        if ($this->state !== 'same' or $this->state != 'default') {
            if (!str_starts_with($this->state, '.')) $this->state = '.' . $this->state;
            if (!str_ends_with($this->state, '.')) $this->state = $this->state . '.';
            if (!str_starts_with($this->output_state, '.')) $this->output_state = '.' . $this->output_state;
            if (!str_ends_with($this->output_state, '.')) $this->output_state = $this->output_state . '.';

        } else {
            $this->output_state = $this;
            $stay_active = false;
        }
        if ($add_to_states) {
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
        if ($this->keyboard) {
            $this->bot->set_keyboard($this->keyboard);
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
        foreach ($this->getRegexes() as $type => $data) {
            foreach ($data as $regex => $args) {
                $function = $args[0];
                $f = (is_callable($function) or is_array($function)) ? $function : [$this, $function];
                $s = $this->getState();
                switch ($type) {
                    default:
                        BotRout::any($regex, $f, $s);
                }
            }
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


}
