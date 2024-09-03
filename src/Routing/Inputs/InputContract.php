<?php

namespace TGMehdi\Routing\Inputs;

use TGMehdi\TelegramBot;

interface InputContract
{

    public function update_type();
    public function parse_input(TelegramBot $bot);
    public function get_extracted_data();
    public function chat_data();
}