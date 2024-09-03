<?php

namespace TGMehdi\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{

    protected $signature = 'tgmehdi:install';

    protected $description = 'Install all of the TG mehdi';

    public function handle()
    {
        $this->info('Installing TG mehdi...');
        $this->call('vendor:publish', ['--tag' => 'tgmehdi-config']);
        $this->call('vendor:publish', ['--tag' => 'tgmehdi-routes']);
        $this->call('vendor:publish', ['--tag' => 'tgmehdi-migrations']);
        $this->call('vendor:publish', ['--tag' => 'tgmehdi-chat-model']);
        $this->call('migrate');
        $this->info('All done!');

    }

}