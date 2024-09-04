<?php


namespace TGMehdi;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use TGMehdi\Routing\BotRout;
use TGMehdi\Routing\Middlewares\MiddlewareContract;
use TGMehdi\States\StateBase;
use TGMehdi\TGTraits\SendApis;
use TGMehdi\TGTraits\SendMessage;
use TGMehdi\TGTraits\SetData;

class TelegramBot
{
    use SendMessage;
    use SendApis;
    use SetData;

    public $token;
    public $bot_url;

    public $keyboard;
    public $reply_message_id;
    public $chat;
    public $message;

       /**
     * @var false|string
     */
    public $update_types = ["message", 'callback_query', 'my_chat_member', 'chat_member', 'chat_boost', 'removed_chat_boost'];

    /**
     * @var mixed
     */
    public $update;

    /**
     * @var false|mixed|string
     */
    public $update_type;
    public $bot = ['name' => 'bot', 'token' => null, 'secret_token' => null];
    public $input;

    public function __construct()
    {
        $this->keyboard = false;
        $this->reply_message_id = false;


    }

    public function another_bot($bot_name, \Closure $func)
    {
        $old_bot = $this->bot;
        $this->switch_bot($bot_name);
        $func();
        $this->switch_bot($old_bot['name']);
    }


    public function get_update_type()
    {
        $d = array_keys($this->data);
        foreach ($this->update_types as $type) {
            if (in_array($type, $d)) {
                return $type;
            }
        }
        return false;
    }


    public function set_reply_message_id($message_id = 0)
    {
        if ($message_id == 0) {
            $this->reply_message_id = $this->message['message_id'];
        } else {
            $this->reply_message_id = $message_id;
        }
    }

    public function set_keyboard($keyboard)
    {
        $this->keyboard = $keyboard;
    }

    public function set_chat_id($chat_id = 0)
    {
        if ($chat_id == 0) {
            $this->chat_id = $this->data["message"]["chat"]["id"];
        } else {
            $this->chat_id = $chat_id;
        }
    }

    public function get_message_type($message = false)
    {
        $out = false;
        $types = BotRout::$types;
        if (!$message) {
            $message = $this->message;
        }
        foreach ($types as $type) {
            if (isset($message[$type])) {
                $out = $type;
            }
        }
        return $out;
    }

    public function download_file($file_id, $file_path)
    {
        $file = json_decode(file_get_contents($this->bot_url . '/getFile?file_id=' . $file_id), true);
        $file_download_path = $file["result"]['file_path'];
        Storage::put($file_path . '/' . $file_download_path,
            Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false

            ])->get("https://api.telegram.org/file/bot{$this->token}/{$file_download_path}"));
        return $file_path . '/' . $file_download_path;
    }


    public function pass_middlewares($middleware)
    {
        if (is_bool($middleware)) {
            return $middleware;
        } elseif (is_string($middleware)) {
            $matches = null;
            preg_match('/([^:]+):?(.+)?/', $middleware, $matches);
            $class = BotKernel::$middlewares[$matches[1]];
            $new = new $class();
            return call_user_func_array([$new, 'handle'], array_merge([$this], (isset($matches[2]) ? explode(',', $matches[2]) : [])));
        } elseif (is_array($middleware)) {
            foreach ($middleware as $m) {
                $res = $this->pass_middlewares($m);
                if (!$res) {
                    return false;
                }
            }
        } else if ($middleware instanceof MiddlewareContract) {
            return $middleware->handle($this);
        }
        return true;
    }


    public function get_chat_id()
    {
        return $this->chat_id;

    }


    public function chat($rewrite = false)
    {
        if (empty($this->chat) or $rewrite) {
            $this->chat_find_or_create();
        }
        return $this->chat;
    }

    public function another_chat($chat_id, \Closure $func)
    {
        $t = $this->chat_id;
        $this->chat_id = $chat_id;
        $func();
        $this->chat_id = $t;
    }


    public function message_init($s)
    {
        return $s;

    }

    public function send_keyboard($keyboard)
    {
//        if ($keyboard instanceof InlineKeyboard) {
//            $options['message_id'] = $this->message_object['message_id'];
//            $options['reply_markup'] = $keyboard->render();
//            $this->send_reply('editMessageReplyMarkup', $options, true);
//        } else {
        $this->set_keyboard($keyboard);
//        }
    }

    public function switch_bot($bot_name)
    {
        $this->bot = config('tgmehdi.bots.' . $bot_name);
        $this->bot['name'] = $bot_name;
        $this->token = $this->bot['token'];
        $this->bot_url = "https://api.telegram.org/bot" . $this->token;
        $this->data = null;
        $this->keyboard = null;
        $this->chat_id = null;
        $this->chat = null;
        $this->input = null;
        $this->update_type = null;
        $this->message = null;
        $this->state_class = null;
        $this->old_reply = null;
        $this->reply_message_id = null;
        $this->update = null;

    }

    public function exec($func, $args = [])
    {
        return general_call($this, $func, $args, $this->state_class);
    }

}
