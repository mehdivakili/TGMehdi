<?php

namespace TGMehdi\Routing\Commands;

use Illuminate\Support\Arr;
use TGMehdi\Routing\Inputs\InputContract;

class RegexCommand implements CommandContract
{
    use CommandHelper;

    private $matches;

    private function combine($value,$key)
    {
        $res = [];
        foreach ($value as $k => $v) {
            if (isset($key[$k]))
                $res[$key[$k]] = $v;
            else
                $res[$k] = $v;
        }
        return $res;
    }

    public function __construct(protected string $pattern, protected array $keys)
    {
    }

    public function is_matched(InputContract $input)
    {
        return preg_match($this->pattern, $input->get_extracted_data()['text'], $this->matches);
    }

    public function get_extracted_args(): array
    {
        return $this->combine(array_slice($this->matches,1), $this->keys);

    }

    public function is_support_input(InputContract $input)
    {
        if (in_array($input->update_type(), ['message', 'callback_query'])) {
            return true;
        }
        return false;
    }
}