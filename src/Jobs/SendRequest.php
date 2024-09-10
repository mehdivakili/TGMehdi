<?php

namespace TGMehdi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use TGMehdi\Jobs\Middleware\RedisThrottle;

class SendRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    private mixed $bot_name;
    private mixed $params;
    private mixed $url;

    public function __construct($bot_name, $url, $params)
    {
        $this->bot_name = $bot_name;
        $this->params = $params;
        $this->url = $url;
    }

    public function handle()
    {
        $token = config('tgmehdi.bots.' . $this->bot_name . '.token');
        return Http::connectTimeout(20)->withOptions(['proxy' => config('tgmehdi.proxy', null), 'verify' => false])->post("https://api.telegram.org/bot" . $token . '/' . $this->url, $this->params)->json();
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