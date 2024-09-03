<?php

namespace TGMehdi\Routing\Commands;

use TGMehdi\Routing\Inputs\InputContract;

class AlwaysCommand implements CommandContract
{
    use CommandHelper;

    public function is_matched(InputContract $input)
    {
        return true;
    }

    public function get_extracted_args(): array
    {
        return [];
    }

    public function is_support_input(InputContract $input)
    {
        return true;
    }
}