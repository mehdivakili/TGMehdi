<?php


namespace TGMehdi;


use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use TGMehdi\Facades\ChatFacade;
use TGMehdi\Routing\BotRout;
use TGMehdi\Routing\Middlewares\MiddlewareContract;
use TGMehdi\States\StateBase;
use TGMehdi\Types\InlineKeyboard;
use TGMehdi\Types\ReplyKeyboard;

class TelegramBot
{
    use SendMessage;


    public $token;
    public $bot_url;

    public $data;
    public $chat_id;
    public $chat_type;
    public $chat_status;


    public null|ReplyKeyboard $keyboard;
    public $reply_message_id;
    private $chat;
    public $message;

    private array $chat_data;
    private bool $chat_data_changed = false;
    private bool $chat_temp_delete = false;
    /**
     * @var false|string
     */
    public $update_types = ["message", 'callback_query', 'my_chat_member', 'chat_member', 'chat_boost', 'removed_chat_boost'];

    /**
     * @var mixed
     */
    public $update;

    public StateBase|null $state_class = null;
    /**
     * @var false|mixed|string
     */
    public $update_type;
    public $bot = ['name' => 'bot', 'token' => null, 'secret_token' => null];
    public $input;

    public function __construct()
    {
        $this->keyboard = null;
        $this->reply_message_id = false;


    }

    public function another_bot($bot_name, \Closure $func)
    {
        $old_bot = $this->bot;
        $this->switch_bot($bot_name);
        $func();
        $this->switch_bot($old_bot['name']);
    }


    public function data_init($r)
    {
        $this->data = $r;
        $this->update_type = $this->get_update_type();
        if (!$this->update_type) {
            die(200);
        }
        $input_class = BotKernel::$input_parsers[$this->update_type];
        $this->input = new $input_class();
        $this->input->parse_input($this);
        $chat_data = $this->input->chat_data();
        $this->chat_id = $chat_data['id'];
        $this->chat_type = $chat_data['type'];
        if (empty($this->bot['cache_optimization'])) {
            $this->chat();
        } else {
            $this->chat_data();
            $this->chat_status = $this->chat_data('status');
            $save_date = $this->chat_data('save_date');
            if (!$save_date or now()->diffInMinutes(Carbon::createFromTimestamp($save_date)) > 90) {
                $this->chat();
                $this->chat->status = $this->chat_status;
                $this->chat->save();
            }
        }
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


    public function set_webhook()
    {
        $site_url = route('tgmehdi.bot', ['bot_name' => $this->bot['name']]);
        $token = $this->token;
        $secret_token = $this->bot['secret_token'];;
        return Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false

        ])->get("https://api.telegram.org/bot$token/setWebhook?url=$site_url"
            . (($secret_token) ? "&secret_token=$secret_token" : '')
            . "&allowed_updates=" . json_encode($this->update_types));
    }

    public function delete_webhook()
    {
        return Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false

        ])->get("https://api.telegram.org/bot{$this->token}/deleteWebhook?drop_pending_updates=True");
    }

    public function restart_webhook()
    {
        $this->delete_webhook();
        return $this->set_webhook();
    }


    public function set_reply_message_id($message_id = 0)
    {
        if ($message_id == 0) {
            $this->reply_message_id = $this->message['message_id'];
        } else {
            $this->reply_message_id = $message_id;
        }
    }

    public function set_keyboard(ReplyKeyboard $keyboard)
    {
        if ($this->keyboard) {
            $this->send_reply("", []);
        }
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

    public function change_status($status = 0)
    {
        if (!empty($this->state_class)) {
            $this->state_class->beforeExit();
            if (!$this->state_class->canExit()) {
                return false;
            }
        }
        if ($status instanceof StateBase) {
            $status->beforeEnter();
            if ($status->canEnter()) {
                $this->state_class?->afterExit();
                $status->afterEnter();
                $this->state_class = $status;
            } else {
                return false;
            }
            if ($status->is_state_change) {
                $status = $status->getEnterState();
            } else {
                $status = ".same.";
            }
        }
        if (!str_starts_with($status, '.')) $status = '.' . $status;
        if (!str_ends_with($status, '.')) $status = $status . '.';
        if ($status != '.same.') {
            $this->chat_status = $status;
            $this->chat_data('status', $status);
            $this->set_state($status);
        }
        return true;
    }

    public function temp($key = null, $text = null)
    {
        if (empty($this->bot['cache_optimization']) and $this->bot['cache_optimization'] == false) {
            if ($text === null) {
                $d = json_decode($this->chat->temp_text, true);
                if (in_array($key, array_keys($d))) {
                    return $d[$key];
                }
                return "";
            }
            $data = json_decode($this->chat->temp_text, true);
            $data[$key] = $text;
            $this->chat->temp_text = json_encode($data);
            $this->chat->save();
            return $text;
        } else {
            if (!isset($this->chat_temp))
                $this->chat_temp = Redis::hgetall("{$this->bot['name']}_chat_{$this->chat_id}.temp");
            if (!is_null($text))
                $this->chat_temp[$key] = $text;
            if (is_null($key))
                return $this->chat_temp;
            if (isset($this->chat_temp[$key]))
                return $this->chat_temp[$key];
            return null;
        }
    }

    public function del_temp($key = null)
    {
        if (!is_null($key)) {
            unset($this->chat_temp[$key]);
        } else {
            $this->chat_temp = [];
        }
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

    private function chat_find_or_create()
    {
        $chat = ChatFacade::where('chat_id', $this->chat_id)->where('bot_name', $this->bot['name'])->first();
        if (empty($chat)) {
            $chat = app("tgmehdi.chat");
            $chat->chat_id = $this->chat_id;
            $chat->bot_name = $this->bot['name'];
            $chat->type = $this->chat_type;
            $chat->status = '.start.';
            $chat->temp_text = '{}';
            if (in_array($chat->type, $this->bot['allowed_chats'])) {
                $chat->save();
            }
        }
        $this->chat = $chat;
        if(!$this->chat_status) {
            $this->chat_status = $this->chat->status;
            $this->chat_data('status', $this->chat_status);
        }
        $this->chat_data('save_date', now()->timestamp);
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

    public function set_state(mixed $real_status)
    {
        $states = [];
        foreach (StateBase::$states as $state) {
            if ($state['type'] != 'state') continue;
            if ($state['stay'] and str_starts_with($real_status, $state['state']->getState())) {
                $states[$state['state']->getState()] = $state['state'];
            }
        }
        ksort($states);

        foreach ($states as $state) {
            $state->init($this);
            $state->beforeStay();
            if ($state->canStay()) {
                $state->afterStay();
            } else {
                return false;
            }
        }
        return true;
    }

    public function message_init($s)
    {
        return $s;

    }

    public function send_keyboard($keyboard)
    {
        if ($keyboard instanceof InlineKeyboard and $this->update_type == 'callback_query') {
            $options['message_id'] = $this->message_object['message_id'];
            $options['reply_markup'] = $keyboard->render();
            $this->send_reply('editMessageReplyMarkup', $options, true);
        } else {
            $this->set_keyboard($keyboard);
        }
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

    public function save_chat_state()
    {
        if (empty($this->bot['cache_optimization'])) {
            $this->chat()->status = $this->chat_status;
            $this->chat->save();
        } else {
            if (isset($this->chat_temp) and empty($this->chat_temp)) {
                Redis::unlink("{$this->bot['name']}_chat_{$this->chat_id}.temp");
            } else if (isset($this->chat_temp)) {
                Redis::hmset("{$this->bot['name']}_chat_{$this->chat_id}.temp", $this->temp());
            }
            if ($this->chat_data_changed)
                Redis::hmset("{$this->bot['name']}_chat_{$this->chat_id}.data", $this->chat_data());
        }

    }

    public function chat_data($key = null, $value = null)
    {
        if (!isset($this->chat_data))
            $this->chat_data = Redis::hgetall("{$this->bot['name']}_chat_{$this->chat_id}.data");
        if (is_null($this->chat_data))
            $this->chat_data = [];
        if (!is_null($value) and
            (!isset($this->chat_data[$value]) or
                (isset($this->chat_data[$value]) and $this->chat_data[$value] != $value)
            )
        ) {
            $this->chat_data[$key] = $value;
            $this->chat_data_changed = true;
        }
        if (is_null($key))
            return $this->chat_data;
        if (isset($this->chat_data[$key]))
            return $this->chat_data[$key];
        return null;
    }
}
