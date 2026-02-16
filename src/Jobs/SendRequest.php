<?php

namespace TGMehdi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use TGMehdi\BotController;
use TGMehdi\Jobs\Middleware\RedisThrottle;
use TGMehdi\Facades\StateFacade;

class SendRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    private mixed $bot_name;
    private mixed $params;
    private mixed $url;
    /**
     * @var array|mixed
     */
    private mixed $files;

    public function __construct($bot_name, $url, $params, $files = [])
    {
        $this->bot_name = $bot_name;
        $this->params = $params;
        $this->url = $url;
        $this->files = $files;
    }

    public function handle()
    {
        $token = config('tgmehdi.bots.' . $this->bot_name . '.token');
        $endpoint_url = config('tgmehdi.bots.' . $this->bot_name . '.endpoint_url', 'https://api.telegram.org/bot');
        $req = Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false]);
        foreach ($this->files as $key => $file) {
            $req->attach($key, $file->getFile(), $file->name);
        }

        $res = $req->post($endpoint_url . $token . '/' . $this->url, $this->params)->json();
        StateFacade::pushResult(["url" => $this->url, "bot_name" => $this->bot_name, "params" => $this->params]);
        StateFacade::pushResult($res);
        return $res;
    }

    /**
     * @return array
     */
    public function getMiddleware(): array
    {
        $ms = [new RedisThrottle($this->bot_name . "_messages", 30, 1)];
        if (isset($this->params['chat_id'])) {
            $ms[] = new RedisThrottle($this->bot_name . "_" . $this->params['chat_id'] . "_messages", ($this->params['chat_id'] > 0) ? 2 : 20, 1);
        }
        return $ms;
    }
}