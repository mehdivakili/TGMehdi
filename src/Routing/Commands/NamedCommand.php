<?php

namespace TGMehdi\Routing\Commands;

use Exception;
use TGMehdi\Routing\Inputs\InputContract;

class NamedCommand extends CommandBase implements CommandContract
{
    use CommandHelper;

    public static $routes = [];
    public string $pattern;
    public string $replacement;
    public string $sample;
    private $matches;
    private $parameters;

    private function combine($value, $key)
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


    public function __construct(protected string $path)
    {
        $regexes = [
            'number' => ['\d+', '1'],
            'word' => ['\w+', 'hello'],
            'pos_number' => ['[1-9][0-9]*', '32'],
            'cost' => ['([1-9][0-9]*|0)(\.[0-9]+)?', '32.3'],
            'any' => ['.+', 'any']
        ];
        $par_regex = "/\{\{([^}]+)}}/";
        $parameters = [];
        $pattern = $this->path;
        $sample = $this->path;
        $replacement = $this->path;
        $i = 0;
        while (true) {
            preg_match($par_regex, $pattern, $matches);
            if (!isset($matches[1])) {
                break;
            }
            $p = explode('|', $matches[1]);
            if (count($p) == 2) {
                $reg = $p[1];
                $name = $p[0];
            } else {
                $reg = $p[0];
                $name = $i;
            }
            $i++;
            $n = explode('?', $reg);
            if (count($n) == 2) {
                $reg = $n[0];
                $default = $n[1];
            } else {
                $reg = $n[0];
                $default = null;
            }
            $parameters[$name] = ['regex' => $reg, 'default' => $default];
            $reg = $regexes[$reg];
            $pattern = preg_replace($par_regex, "(" . $reg[0] . ")" . ((!is_null($default)) ? '?' : ''), $pattern);
            $sample = preg_replace($par_regex, $reg[1], $sample);
            $replacement = preg_replace($par_regex, "{{" . $name . "}}", $replacement);
            $i++;
        }
        $this->pattern = $pattern;
        $this->sample = $sample;
        $this->replacement = $replacement;
        $this->parameters = $parameters;

    }

    public function generate_route($args): string
    {
        $p = $this->replacement;
        foreach ($this->parameters as $name => $value) {
            if (isset($args[$name])) {
                $v = $args[$name];
            } else if (!is_null($value['default'])) {
                $v = $value['default'];
            } else {
                throw new Exception("params not enough");
            }
            $p = str_replace('{{' . $name . '}}', $v, $p);
        }
        return $p;
    }

    public function is_matched(InputContract $input)
    {
        return preg_match("/^" . $this->pattern . '$/', $input->get_extracted_data()['text'], $this->matches);
    }

    public function get_extracted_args(): array
    {
        return $this->combine(array_slice($this->matches, 1), array_keys($this->parameters));
    }

    public function is_support_input(InputContract $input)
    {

        if (in_array($input->update_type(), ['message', 'callback_query'])) {
            return true;
        }
        return false;

    }
}