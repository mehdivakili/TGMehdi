<?php

namespace TGMehdi\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DevCommand extends Command
{

    protected $signature = 'tgmehdi:dev';

    protected $description = 'set development mode of the TG mehdi';

    public function handle()
    {
        while (true) {

            try {
                foreach (config('tgmehdi.bots') as $bot => $config) {
                    Http::get(route('tgmehdi.local_bot', ['bot_name' => $bot]), ['be_seen' => true])->throw()->json();
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                break;
            }
        }
    }

}