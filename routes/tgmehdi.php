<?php

use Illuminate\Support\Facades\Route;
use TGMehdi\BotController;

$r = ['as' => 'tgmehdi.', 'prefix' => config('tgmehdi.url_prefix')];
if (config('tgmehdi.middleware')) {
    $r['middleware'] = config('tgmehdi.middleware');
}

Route::group($r, function () {

    $m = [];
    if (config('tgmehdi.management_middleware')) {
        $m = config('tgmehdi.management_middleware');
    }
    $m = array_merge($m, ['web']);
    Route::get('/', BotController::class . '@index')->name('index')->middleware($m);
    Route::group(['prefix' => '{bot_name}'], function () use ($m) {
        Route::post('bot', BotController::class . '@bot')->name('bot');
        Route::group(['middleware' => $m], function () {
            Route::get('update', BotController::class . '@local_bot')->name('local_bot');
            Route::get('set_webhook', BotController::class . '@set_webhook')->name('set_webhook');
            Route::get('delete_webhook', BotController::class . '@delete_webhook')->name('delete_webhook');
            Route::get('restart_webhook', BotController::class . '@restart_webhook')->name('restart_webhook');
        });
    });
});