<?php

namespace TGMehdi\TGTraits;

use Illuminate\Support\Facades\Http;

trait SendApis
{
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

}