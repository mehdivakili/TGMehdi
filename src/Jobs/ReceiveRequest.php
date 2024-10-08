<?php

namespace TGMehdi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use TGMehdi\BotController;
use TGMehdi\Events\DataParsedEvent;
use TGMehdi\Events\ErrorEvent;
use TGMehdi\Jobs\Middleware\RedisThrottle;
use TGMehdi\Routing\TGRout;
use TGMehdi\TelegramBot;

class ReceiveRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    private mixed $bot_name;
    private mixed $data;
    /**
     * @var false|mixed
     */
    private bool $is_error_must_throw = false;
    /**
     * @var mixed|null
     */
    private string|null $secret_token;

    public function __construct($bot_name, $data, $secret_token = null, $is_error_must_throw = false)
    {
        $this->bot_name = $bot_name;
        $this->data = $data;
        $this->is_error_must_throw = $is_error_must_throw;
        $this->secret_token = $secret_token;
    }

    private function without_delayed_message(TelegramBot $telegramBot, $data)
    {
        if ($telegramBot->bot['secret_token'] != null and $this->secret_token != $telegramBot->bot['secret_token'])
            return false;

        $telegramBot->data_init($data);
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

    public function handle(TelegramBot $tg)
    {
        $tg->switch_bot($this->bot_name);
        try {
            $this->without_delayed_message($tg, $this->data);
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
                if ($this->is_error_must_throw)
                    throw $exception2;
                return [
                    ['result' => 'error', 'message' => $exception2->getMessage()],
                    ['result' => 'error', 'message' => $exception->getMessage()]
                ];
            }
            if ($this->is_error_must_throw)
                throw $exception;
            return [['result' => 'error', 'message' => $exception->getMessage()]];
        }
        return BotController::$results;
    }
}