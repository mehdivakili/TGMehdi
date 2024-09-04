<?php

namespace TGMehdi\Actions;

use Illuminate\Events\Dispatcher;
use TGMehdi\Events\States\AfterEnter;
use TGMehdi\Events\States\AfterExit;
use TGMehdi\Events\States\AfterStay;
use TGMehdi\Events\States\BeforeEnter;
use TGMehdi\Events\States\BeforeExit;
use TGMehdi\Events\States\BeforeStay;
use TGMehdi\TelegramBot;

class ObserverBase
{
    public function subscribe(Dispatcher $events)
    {
        $tg = app(TelegramBot::class);
        if (method_exists($this, 'beforeEnter')) {
            $events->listen(BeforeEnter::class, function (BeforeEnter $event) use ($tg) {
                general_call($tg, [$this, 'beforeEnter'], ['event' => $event], $this);
            });
        }
        if (method_exists($this, 'afterEnter')) {
            $events->listen(AfterEnter::class, function (AfterEnter $event) use ($tg) {
                general_call($tg, [$this, 'afterEnter'], ['event' => $event], $this);
            });
        }
        if (method_exists($this, 'beforeExit')) {
            $events->listen(BeforeExit::class, function (BeforeExit $event) use ($tg) {
                general_call($tg, [$this, 'beforeExit'], ['event' => $event], $this);
            });
        }
        if (method_exists($this, 'afterExit')) {
            $events->listen(AfterExit::class, function (AfterExit $event) use ($tg) {
                general_call($tg, [$this, 'afterExit'], ['event' => $event], $this);
            });
        }
        if (method_exists($this, 'beforeStay')) {
            $events->listen(BeforeStay::class, function (BeforeStay $event) use ($tg) {
                general_call($tg, [$this, 'beforeStay'], ['event' => $event], $this);
            });
        }
        if (method_exists($this, 'afterStay')) {
            $events->listen(AfterStay::class, function (AfterStay $event) use ($tg) {
                if (method_exists($this, 'routes')) {
                    $this->routes($event);
                }
                general_call($tg, [$this, 'afterStay'], ['event' => $event], $this);
            });
        }
    }
}