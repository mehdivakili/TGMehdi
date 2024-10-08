<?php

namespace TGMehdi;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use TGMehdi\Jobs\ReceiveRequest;

class BotController extends Controller
{
    public static $results = [];


    public static function add_res(mixed $json)
    {
        self::$results[] = $json;
    }

    public function index()
    {
        return view('tgmehdi::index');
    }

    public function local_bot(Request $request, $bot_name)
    {
        $be_seen = $request->get('be_seen');
        $token = config('tgmehdi.bots.' . $bot_name . '.token');
        $update_types = config('tgmehdi.bots.' . $bot_name . '.update_types');
        $response = Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false
        ])->get("https://api.telegram.org/bot$token/getUpdates?offset=" . (cache('update_id') + 1) . "&allowed_updates=" . json_encode($update_types));
        $j = json_decode($response->body(), true)['result'];
        echo "<hr>" . json_encode($j) . "<hr>";
        foreach ($j as $item) {

            Cache::forever('update_id', cache('update_id') + 1);
            $res = $this->bot($request, $bot_name, !$be_seen, $item);
            echo "<hr>" . json_encode($res) . "<hr>";
            self::$results = [];
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

    public function bot(Request $request, $bot_name, $is_error_must_see = false, $data = null)
    {
        $data = (is_null($data)) ? $request->all() : $data;
        if (config('tgmehdi.bots.' . $bot_name . '.request_queue')) {
            ReceiveRequest::dispatch($bot_name, $data, $request->header('X-Telegram-Bot-Api-Secret-Token'), $is_error_must_see)->onQueue(config('tgmehdi.bots.' . $bot_name . '.update_queue'));
            return [];
        } else {
            ReceiveRequest::dispatchSync($bot_name, $data, $request->header('X-Telegram-Bot-Api-Secret-Token'), $is_error_must_see);
            return self::$results;
        }
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
