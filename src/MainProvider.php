<?php

namespace TGMehdi;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use TGMehdi\Commands\InstallCommand;
use TGMehdi\Commands\DevCommand;
use TGMehdi\Events\ErrorEvent;
use TGMehdi\Events\LogRedisCommand;

class MainProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/tgmehdi.php', 'tgmehdi'
        );
        $this->app->scoped(TelegramBot::class, function ($app) {
            return new TelegramBot();
        });
        $this->app->scoped(\TGMehdi\Routing\TGRout::class, function ($app) {
            return new \TGMehdi\Routing\TGRout();
        });

        $this->app->scoped(\TGMehdi\Routing\BotRout::class, function ($app) {
            return new \TGMehdi\Routing\BotRout();
        });
        $this->app->scoped(\TGMehdi\StateSaver::class, function ($app) {
            return new \TGMehdi\StateSaver();
        });
        $this->app->bind("tgmehdi.chat", function ($app) {
            $c = config("tgmehdi.chat");
            return new $c();
        });
        foreach (glob(__DIR__ . '/Helpers/*.php') as $filename) {
            require_once $filename;
        }
    }

    public function boot()
    {
        AboutCommand::add('TGMehdi', fn() => ['Version' => '1.0.0']);

        $this->publishes([
            __DIR__ . '/../config/tgmehdi.php' => config_path('tgmehdi.php'),
        ], 'tgmehdi-config');
        $this->publishes([
            __DIR__ . '/../routes/bots' => base_path('routes/bots'),
        ], 'tgmehdi-routes');
        $this->loadRoutesFrom(__DIR__ . '/../routes/tgmehdi.php');
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'tgmehdi-migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tgmehdi');
        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/tgmehdi'),
        ], 'tgmehdi-lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tgmehdi');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/tgmehdi'),
        ], 'tgmehdi-views');

        $this->publishes([
            __DIR__ . '/../stubs/Chat.php' => app_path('Models/Chat.php'),
        ], 'tgmehdi-chat-model');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                DevCommand::class,
            ]);
        }
        Event::listen(
            CommandExecuted::class,
            LogRedisCommand::class,
        );


    }
}