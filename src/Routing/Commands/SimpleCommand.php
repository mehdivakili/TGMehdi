<?php

namespace TGMehdi\Routing\Commands;

use TGMehdi\Routing\Inputs\InputContract;

class SimpleCommand extends CommandBase implements CommandContract
{
    use CommandHelper;

    public
    function __construct(public string $command)
    {
    }

    public
    function get_extracted_args(): array
    {
        return ['command' => $this->command];
    }

    public
    function is_matched(InputContract $input)
    {
        $data = $input->get_extracted_data();
        return $data['text'] == $this->command;
    }

    public
    function is_support_input(InputContract $input)
    {
        if (in_array($input->update_type(), ['message', 'callback_query'])) {
            return true;
        }
        return false;
    }
}