<?php

namespace TGMehdi\States;

use TGMehdi\TelegramBot;

trait StateHelper
{
    public $data;

    function goto($state, $data = [], $keys = [])
    {
        if (is_string($state)) {
            if (isset(StateBase::$states[$state])) {
                switch (StateBase::$states[$state]['type']) {
                    case 'abbr':
                        return $this->goto(StateBase::$states[$state]['state'], $data, $keys);
                    default:
                        $state = StateBase::$states[$state]['state'];
                        $state->init($this->bot);
                        $f_d = [];
                        if (!empty($keys)) {
                            foreach ($data as $key => $value) {
                                if (is_numeric($key) and count($keys) < $key)
                                    $f_d[$keys[$key]] = $value;
                                else
                                    $f_d[$key] = $value;
                            }
                            $state->setData($f_d);
                        } else {
                            $state->setData($data);
                        }
                }

            } else {
                throw new \Exception("State $state does not exist");
            }
        }
        $this->bot->change_status($state);
    }

    private function general_call($func, $args)
    {
        return general_call($this->bot, $func, $args, $this);
    }

    public function exec($function, $args = [])
    {
        return $this->general_call($function, $args);
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
