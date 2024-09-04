<?php

namespace TGMehdi;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use TGMehdi\Controllers\TGMehdi;
use TGMehdi\Events\Command\AfterCommandCheck;
use TGMehdi\Events\Command\AfterCommandExecuted;
use TGMehdi\Events\Command\BeforeCommandCheck;
use TGMehdi\Events\Routing\AfterGetRoutes;
use TGMehdi\Events\Routing\AfterSetState;
use TGMehdi\Events\Routing\BeforeDataReceived;
use TGMehdi\Events\Routing\BeforeGetRoutes;
use TGMehdi\Events\Routing\BeforeSetState;
use TGMehdi\Events\Telegram\ErrorEvent;
use TGMehdi\Events\Telegram\RequestIsDone;
use TGMehdi\Events\Telegram\TGFinished;
use TGMehdi\Events\Telegram\TGInitialized;
use TGMehdi\Routing\TGRout;

class BotController extends Controller
{
    public $r = null;

    private static $results = [];


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
            TGInitialized::dispatch($tg);
            $this->bot_without_delayed_message($tg, $request);
            RequestIsDone::dispatch($tg);
            TGFinished::dispatch($tg);
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
        BeforeDataReceived::dispatch($telegramBot, $this->r);
        $real_status = $telegramBot->chat_status;
        BeforeSetState::dispatch($telegramBot, $real_status);
        $telegramBot->set_state($real_status);
        AfterSetState::dispatch($telegramBot, $real_status);
        BeforeGetRoutes::dispatch($telegramBot, $real_status);
        $routes_with_pr = TGRout::get_routes($telegramBot->bot['route'], $telegramBot->chat_type, $real_status)[$telegramBot->chat_type];
        AfterGetRoutes::dispatch($telegramBot, $routes_with_pr);
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
                    BeforeCommandCheck::dispatch($telegramBot, $command);
                    if ($command->is_support_input($telegramBot->input) and $command->can_execute()) {
                        AfterCommandCheck::dispatch($telegramBot, $command);
                        $s = $command->execute();
                        AfterCommandExecuted::dispatch($telegramBot, $command, $s);
                        return $s;
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
