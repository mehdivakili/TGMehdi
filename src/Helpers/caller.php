<?php

use Illuminate\Support\Str;
use TGMehdi\States\StateBase;

if (!function_exists('general_call')) {

    function return_parameters_with_reflection($tg, $s, $args, $state_class)
    {
        $params = $s->getParameters();
        $calls = [];
        $marked = [];
        $not_init_params = [];
        foreach ($params as $param) {
            $type = $param->getType();
            $name = $param->getName();
            if ($type == "TGMehdi\TelegramBot" or (!$type and in_array($name, ['tg', 'telegramBot', 'bot']))) {
                $calls[$name] = $tg;
            } else if ((class_exists($type) and (new ReflectionClass($type))->isSubclassOf(StateBase::class)) or (!$type and in_array($name, ['state', 'st']))) {
                $calls[$name] = $state_class;
            } else if ($type == 'array' or (!$type and $name == 'args')) {
                $calls[$name] = $args;
            } else if (array_key_exists($name, $args)) {
                $calls[$name] = $args[$name];
                $marked[$name] = true;
            } else {
                $not_init_params[$name] = $param->getPosition();
            }
        }
        foreach ($not_init_params as $name => $position) {
            foreach ($args as $key => $value) {
                if (!array_key_exists($key, $marked)) {
                    $calls[$name] = $value;
                    $marked[$key] = true;
                    break;
                }
            }
        }
        return $calls;
    }

    function call_with_dependency_inversion(\TGMehdi\TelegramBot $tg, $func, $args, $state_class)
    {
        if (is_array($func) and count($func) == 2) {
            if (is_string($func[0])) {
                $s = (new ReflectionClass($func[0]))->getConstructor();
                $params = return_parameters_with_reflection($tg, $s, $args, $state_class);
                if (is_null($params)) return false;
                $class = new $func[0](...$params);
            } else {
                $class = $func[0];
            }
            $s = new ReflectionMethod($func[0], $func[1]);
            $params = return_parameters_with_reflection($tg, $s, $args, $state_class);
            if (is_null($params)) return false;
            return call_user_func_array([$class, $func[1]], $params);
        } else {
            $s = new ReflectionFunction($func);
            $params = return_parameters_with_reflection($tg, $s, $args, $state_class);
            if (is_null($params)) return false;
            return call_user_func_array($func, $params);
        }
    }

    function general_call(\TGMehdi\TelegramBot $tg, $func, $args = [], $state_class = null, $message_status = null)
    {
        $global_commands = ['goto', 'send', 'edit', 'return'];
        if (is_null($func)) return true;
        if (is_bool($func)) return $func;
        if (!isset($tg->state_class) or $state_class != null)
            $tg->state_class = $state_class;
        if ($state_class)
            $state_class->init($tg);
        if (is_array($func) and key_exists(0, $func) and in_array($func[0], $global_commands)) {
            if ($func[0] == 'goto') {

                $class = new StateBase('trash_state');
                $class->init($tg);
                if (count($func) == 3)
                    return $class->goto($func[1], $func[2], $args);
                else
                    return $class->goto($func[1]);
            } else if ($func[0] == 'send') {
                return general_call($tg, $func[1], $args, $state_class, "send");
            } else if ($func[0] == 'edit') {
                return general_call($tg, $func[1], $args, $state_class, "edit");
            } else if ($func[0] == 'return') {
                return general_call($tg, $func[1], $args, $state_class, "return");
            }
        } else if (is_callable($func)) {
            return general_call($tg, call_with_dependency_inversion($tg, $func, $args, $state_class), $args, $state_class);
        } else if ($tg and ($func instanceof \TGMehdi\Types\Media)) {
            $s = $func->render($tg);
            if (($message_status == 'edit') or (is_null($message_status) and $tg->get_update_type() == 'callback_query')) {
                if ($s['caption']) {
                    $tg->send_reply("editMessageCaption", $s);
                }
            } else if ($message_status == 'send' or (is_null($message_status) and $tg->get_update_type() == 'message')) {
                $tg->send_reply("send" . Str::title($func->type), $s);
            }
        } else if ($tg and (is_string($func) or $func instanceof \stdClass or $func instanceof \Illuminate\View\View)) {
            if (($message_status == 'edit') or (is_null($message_status) and $tg->get_update_type() == 'callback_query')) {
                $tg->edit_message_text($func);
            } else if ($message_status == 'send' or (is_null($message_status) and $tg->get_update_type() == 'message')) {
                $tg->send_text($func);
            } else if ($message_status == 'return') {
                if ($func instanceof \Illuminate\View\View) {
                    return $func->render();
                } else {
                    return $func;
                }
            }
            return false;
        } else if ($tg and $func instanceof \TGMehdi\Types\InlineMessage) {
            $s = $func->render($tg);
            if (!empty($s['text'])) {
                $text = $s['text'];
                $options = ['reply_markup' => $s['reply_markup']];
                if (($message_status == 'edit') or (is_null($message_status) and $tg->get_update_type() == 'callback_query')) {
                    $tg->edit_message_text($text, options: $options);
                } else {
                    $tg->send_text($text, options: $options);

                }
            }
        }

        return $func;
    }
}
