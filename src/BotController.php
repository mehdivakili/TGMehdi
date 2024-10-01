<?php

namespace TGMehdi;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use TGMehdi\Controllers\TGMehdi;
use TGMehdi\Events\DataParsedEvent;
use TGMehdi\Events\ErrorEvent;
use TGMehdi\Routing\BotRout;
use TGMehdi\Routing\TGRout;
use TGMehdi\States\StateBase;
use TGMehdi\Types\ReplyKeyboard;

class BotController extends Controller
{
    public $r = null;

    public static $results = [];


    public static function add_res(mixed $json)
    {
        self::$results[] = $json;
    }

    private function general_call(TelegramBot $telegramBot, $func, $args, $state_class)
    {
        return general_call($telegramBot, $func, $args, $state_class);
    }

    public function index()
    {
        return view('tgmehdi::index');
    }

    private function combine($key, $value)
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

    public function local_bot(Request $request, TelegramBot $tg, $bot_name)
    {
        $be_seen = $request->get('be_seen');
//        DB::connection()->enableQueryLog();
//        Redis::enableEvents();
        $tg->switch_bot($bot_name);
        $response = Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false
        ])->get("https://api.telegram.org/bot$tg->token/getUpdates?offset=" . (cache('update_id') + 1) . "&allowed_updates=" . json_encode($tg->update_types));
        $j = json_decode($response->body(), true)['result'];
        echo "<hr>" . json_encode($j) . "<hr>";
        foreach ($j as $item) {
            $this->r = $item;
            Cache::forever('update_id', cache('update_id') + 1);
            $res = $this->bot($tg, $request, $bot_name, !$be_seen);
            echo "<hr>" . json_encode($res) . "<hr>";
            $update_id = $item['update_id'];
            Cache::forever('update_id', $update_id);
//            echo "<hr><h3>DEBUG</h3>";
//            echo "db query log <br> ";
//            foreach (DB::getQueryLog() as $query) {
//                echo json_encode($query) . "<br>";
//            }
//            echo "<br>redis query log <br>";
//            foreach (Context::get('redis-command') as $command) {
//                echo json_encode($command) . "<br>";
//            }
//            echo die("<hr>");

        }
        return view('tgmehdi::update');
    }

    public function bot(TelegramBot $tg, Request $request, $bot_name, $is_error_must_see = false)
    {
        $tg->switch_bot($bot_name);
        try {
            $this->bot_without_delayed_message($tg, $request);
            if (!$tg->send_reply('', []) and $tg->keyboard and !$tg->keyboard->is_sended) {
                if ($tg->state_class and $tg->state_class->getDefaultText() != "test")
                    $tg->send_text($tg->state_class->getDefaultText(), true);
            }
            $tg->save_chat_state();
        } catch (\Exception $exception) {
            if (isset($tg->bot['debug']) and $tg->bot['debug']) {
                $tg->send_text($exception->getMessage(), true);

            }
            try {
                ErrorEvent::dispatch($tg, $exception);
            } catch (\Exception $exception2) {
                if ($is_error_must_see)
                    throw $exception2;
                return [
                    ['result' => 'error', 'message' => $exception2->getMessage()],
                    ['result' => 'error', 'message' => $exception->getMessage()]
                ];
            }
            if ($is_error_must_see)
                throw $exception;
            return [['result' => 'error', 'message' => $exception->getMessage()]];
        }
        return self::$results;
    }

    private function bot_without_delayed_message(TelegramBot $telegramBot, Request $request)
    {
        if ($telegramBot->bot['secret_token'] != null and $request->header('X-Telegram-Bot-Api-Secret-Token') != $telegramBot->bot['secret_token'])
            return false;
        if ($this->r == null) {
            $this->r = $request->all();
        }
        $telegramBot->data_init($this->r);
        DataParsedEvent::dispatch($telegramBot);
        $real_status = $telegramBot->chat_status;
        $routes_with_pr = TGRout::get_routes($telegramBot->bot['route'], $telegramBot->chat_type, $real_status)[$telegramBot->chat_type];
        $telegramBot->set_state($real_status);
        $pr = array_keys($routes_with_pr);
        sort($pr);
        $pr = array_reverse($pr);
        foreach ($pr as $p) {
            $routes = $routes_with_pr[$p];
            $vc = array_keys($routes);
            sort($vc);
            $vc = array_reverse($vc);
            foreach ($vc as $status) {
                if (!str_starts_with($real_status, $status)) continue;
                foreach ($routes[$status] as $command) {
                    $command->set_tg($telegramBot);
                    if ($command->is_support_input($telegramBot->input) and $command->can_execute()) {
                        return $command->execute();
                    }
                }
            }
        }
        return true;
    }


    public function set_webhook(TelegramBot $telegramBot, $bot_name)
    {
        $telegramBot->switch_bot($bot_name);
        return $telegramBot->set_webhook();
    }

    public function delete_webhook(TelegramBot $telegramBot, $bot_name)
    {
        $telegramBot->switch_bot($bot_name);
        return $telegramBot->delete_webhook();
    }

    public function restart_webhook(TelegramBot $telegramBot, $bot_name)
    {
        $telegramBot->switch_bot($bot_name);
        return $telegramBot->restart_webhook();
    }

}
