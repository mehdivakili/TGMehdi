<?php

namespace TGMehdi\Routing\Commands;


use TGMehdi\Routing\Inputs\InputContract;
use TGMehdi\TelegramBot;

interface CommandContract
{
    public function get_extracted_args(): array;

    public function set_tg(TelegramBot $tg);

    public function set_state($state);

    public function execute();

    public function can_execute();

    public function is_support_input(InputContract $input);
}