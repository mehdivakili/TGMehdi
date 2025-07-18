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
    private array|null $chat_temp = null;
    private bool $temp_changed = false;
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
    public array $m_text = [];
    public array $m_state = [];
    public array $m_temp = [];

    public function __construct()
    {
        $this->keyboard = null;
        $this->reply_message_id = false;


    }

    public function another_bot($bot_name, \Closure $func, $options = [])
    {
        $old_bot = $this->bot;
        $this->switch_bot($bot_name);
        foreach ($options as $key => $option) {
            $this->bot[$key] = $option;
        }
        $func();
        if ($old_bot['name'] != 'bot')
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
        if (empty($this->bot['cache_optimization']) or $this->bot['cache_optimization'] !== true) {
            $this->chat_status = $this->chat()->status;
        } else {
            if (!$this->chat_data('save_date')) {
                $this->chat_status = $this->chat()->status;
            } else {
                $this->chat_status = $this->chat_data('status');
            }
        }
    }

    public function set_message_state($real_text, $state, $temp, $message_id)
    {
        $this->m_text[$message_id] = $real_text;
        $this->m_state[$message_id] = $state;
        $this->m_temp[$message_id] = $temp;
    }


    public function get_update_type()
    {
        $d = array_keys($this->data);
        foreach ($this->bot["update_types"] as $type) {
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
        $secret_token = $this->bot['secret_token'];
        $req = Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false]);
        $data = ['url' => $site_url, 'allowed_updates' => json_encode($this->bot["update_types"])];
        if ($secret_token) {
            $data['secret_token'] = $secret_token;
        }
        if (config('tgmehdi.self_signed_webhook.active')) {
            $data['ip_address'] = $_SERVER['SERVER_ADDR'];
            $req->attach('certificate', Storage::get(config('tgmehdi.self_signed_webhook.certificate')));
        }
        $endpoint_url = config('tgmehdi.bots.' . $this->bot['name'] . '.endpoint_url', 'https://api.telegram.org/bot');

        return $req->post($endpoint_url . "$token/setWebhook", $data);
    }

    public function delete_webhook()
    {
        $endpoint_url = config('tgmehdi.bots.' . $this->bot['name'] . '.endpoint_url', 'https://api.telegram.org/bot');
        return Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false

        ])->get("{$endpoint_url}{$this->token}/deleteWebhook?drop_pending_updates=True");
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
        $endpoint_url = config('tgmehdi.bots.' . $this->bot['name'] . '.endpoint_url', 'https://api.telegram.org/bot');
        Storage::put($file_path . '/' . $file_download_path,
            Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false

            ])->get("{$endpoint_url}{$this->token}/{$file_download_path}"));
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
            $this->set_state($status);
        }
        return true;
    }

    public function change_message_status($status = 0, $message_id = null)
    {
        if ($message_id === null and $this->get_update_type() != "callback_query") {
            $message_id = 0;
        } else if ($message_id === null) {
            $message_id = $this->input->message_id;
        }
//        if (!empty($this->state_class)) {
//            $this->state_class->beforeExit();
//            if (!$this->state_class->canExit()) {
//                return false;
//            }
//        }
//        if ($status instanceof StateBase) {
//            $status->beforeEnter();
//            if ($status->canEnter()) {
//                $this->state_class?->afterExit();
//                $status->afterEnter();
//                $this->state_class = $status;
//            } else {
//                return false;
//            }
//            if ($status->is_state_change) {
//                $status = $status->getEnterState();
//            } else {
//                $status = ".same.";
//            }
//        }
        if (!str_starts_with($status, '.')) $status = '.' . $status;
        if (!str_ends_with($status, '.')) $status = $status . '.';
        if ($status != '.same.') {
            $this->m_state[$message_id] = $status;
        }
        return true;
    }

    public function get_message_status($message_id = null)
    {
        if ($message_id === null and $this->get_update_type() != "callback_query") {
            $message_id = 0;
        } else if ($message_id === null) {
            $message_id = $this->input->message_id;
        }
        return $this->m_state[$message_id] ?? null;
    }

    public function temp($key = null, $text = null)
    {
        if (empty($this->bot['cache_optimization']) and $this->bot['cache_optimization'] == false) {
            if ($text === null) {
                $d = json_decode($this->chat()->temp_text, true);
                if (in_array($key, array_keys($d))) {
                    return $d[$key];
                }
                return "";
            }
            $data = json_decode($this->chat()->temp_text, true);
            $data[$key] = $text;
            $this->chat()->temp_text = json_encode($data);
            return $text;
        } else {
            if (is_null($this->chat_temp)) {
                $name = (isset($this->bot['shared']) and !empty($this->bot['shared'])) ? $this->bot['shared'] : $this->bot['name'];
                if ($name != "nothing")
                    $this->chat_temp = Redis::hgetall("{$name}_chat_{$this->chat_id}.temp");
                else {
                    $this->chat_temp = [];
                }
            }
            if (!is_null($text) and
                (!isset($this->chat_temp[$key]) or
                    (isset($this->chat_temp[$key]) and $text != $this->chat_temp[$key])
                )
            ) {
                $this->chat_temp[$key] = $text;
                $this->temp_changed = true;
            }
            if (is_null($key))
                return $this->chat_temp;
            if (isset($this->chat_temp[$key]))
                return $this->chat_temp[$key];
            return null;
        }
    }

    public function message_temp($key = null, $text = null, $message_id = null)
    {

        if ($message_id === null and $this->get_update_type() != "callback_query") {
            $message_id = 0;
        } else if ($message_id === null) {
            $message_id = $this->input->message_id;
        }

        if ($text === null) {
            if (!isset($this->m_temp[$message_id]) or $this->m_temp[$message_id] === null) return null;
            if ($key === null) return $this->m_temp[$message_id];
            if (in_array($key, array_keys($this->m_temp[$message_id]))) {
                return $this->m_temp[$message_id][$key];
            }
            return "";
        }
        if (!isset($this->m_temp[$message_id]) or $this->m_temp[$message_id] === null) $this->m_temp[$message_id] = [];
        $this->m_temp[$message_id][$key] = $text;
        return $text;
    }

    public function del_message_temp($key = null, $message_id = null)
    {
        if ($message_id === null and $this->get_update_type() != "callback_query") {
            $message_id = 0;
        } else if ($message_id === null) {
            $message_id = $this->input->message_id;
        }
        if (!is_null($key) and !is_null($this->m_temp[$message_id])) {
            unset($this->m_temp[$message_id][$key]);
        } else {
            $this->m_temp[$message_id] = null;
        }

    }

    public function message_text($message_id = null)
    {
        if ($message_id === null and $this->get_update_type() != "callback_query") {
            $message_id = 0;
        } else if ($message_id === null) {
            $message_id = $this->input->message_id;
        }
        return $this->m_text[$message_id] ?? "";
    }

    public function del_chat_data($key = null)
    {
        if (!is_null($key)) {
            unset($this->chat_data[$key]);
        } else {
            $this->chat_data = [];
        }
    }

    public function del_temp($key = null)
    {
        if (!is_null($key)) {
            unset($this->chat_temp[$key]);
        } else {
            $this->chat_temp = [];
        }
        $this->temp_changed = true;
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
        $name = (isset($this->bot['shared']) and !empty($this->bot['shared'])) ? $this->bot['shared'] : $this->bot['name'];
        if ($name == 'nothing') {
            $chat = null;
        } else {
            $chat = ChatFacade::where('chat_id', $this->chat_id)->where('bot_name', $name)->first();
        }
        if (empty($chat)) {
            $chat = app("tgmehdi.chat");
            $chat->chat_id = $this->chat_id;
            $chat->bot_name = $this->bot['name'];
            $chat->type = $this->chat_type;
            $chat->status = '.start.';
            $chat->temp_text = '{}';
        }
        $this->chat = $chat;
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

    public function switch_bot($bot_name)
    {
        $this->bot = config('tgmehdi.bots.' . $bot_name);
        $this->bot['name'] = $bot_name;
        $this->token = $this->bot['token'];
        $endpoint_url = config('tgmehdi.bots.' . $this->bot['name'] . '.endpoint_url', 'https://api.telegram.org/bot');
        $this->bot_url = $endpoint_url . $this->token;
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
        $this->chat_data = [];
        $this->chat_temp = null;
        $this->chat_status = null;
        $this->chat_data_changed = false;
        $this->temp_changed = false;
        $this->m_text = [];
        $this->m_temp = [];
        $this->m_state = [];
    }

    public function exec($func, $args = [])
    {
        return general_call($this, $func, $args, $this->state_class);
    }

    public function save_chat_state()
    {

        $name = (isset($this->bot['shared']) and !empty($this->bot['shared'])) ? $this->bot['shared'] : $this->bot['name'];
        if ($name != "nothing") {
            if (empty($this->bot['cache_optimization']) and in_array($this->chat_type, $this->bot['allowed_chats'])) {
                $this->chat()->status = $this->chat_status;
                $this->chat->save();
            } else {
                $this->chat_data('status', $this->chat_status);
                if (!$this->chat_data('save_date') or Carbon::createFromTimestamp($this->chat_data('save_date'))->diffInMinutes(now(), true) > 90) {
                    if (in_array($this->chat_type, $this->bot['allowed_chats'])) {
                        $this->chat();
                        $this->chat->status = $this->chat_status;
                        $this->chat->save();
                        $this->chat_data('save_date', now()->timestamp);
                    }
                }
                if ($this->temp_changed) {
                    if (empty($this->chat_temp)) {
                        Redis::unlink("{$name}_chat_{$this->chat_id}.temp");
                    } else {
                        Redis::hmset("{$name}_chat_{$this->chat_id}.temp", $this->temp());
                    }
                }
                if ($this->chat_data_changed)
                    Redis::hmset("{$name}_chat_{$this->chat_id}.data", $this->chat_data());
            }
        }

    }

    public function chat_data($key = null, $value = null)
    {
        if (!isset($this->chat_data)) {
            $name = (isset($this->bot['shared']) and !empty($this->bot['shared'])) ? $this->bot['shared'] : $this->bot['name'];
            if ($name != "nothing")
                $this->chat_data = Redis::hgetall("{$name}_chat_{$this->chat_id}.data");
            else
                $this->chat_data = [];
        }
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
