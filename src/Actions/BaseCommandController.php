<?php


namespace TGMehdi\Actions;


use TGMehdi\TelegramBot;

abstract class BaseCommandController
{
    protected $telegramBot;

    public function __construct(TelegramBot $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }
}
