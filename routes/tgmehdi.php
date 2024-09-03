<?php

use Illuminate\Support\Facades\Route;
use TGMehdi\BotController;

$r = ['as' => 'tgmehdi.', 'prefix' => config('tgmehdi.url_prefix')];
if (config('tgmehdi.middleware')) {
    $r['middleware'] = config('tgmehdi.middleware');
}
Route::group($r, function () {
    Route::get('/', BotController::class . '@index')->name('index');
    Route::group(['prefix' => '{bot_name}'], function () {
        Route::post('bot', BotController::class . '@bot')->name('bot');
        Route::group(['middleware' => ['web']], function () {
            Route::get('update', BotController::class . '@local_bot')->name('local_bot');
            Route::get('set_webhook', BotController::class . '@set_webhook')->name('set_webhook');
            Route::get('delete_webhook', BotController::class . '@delete_webhook')->name('delete_webhook');
            Route::get('restart_webhook', BotController::class . '@restart_webhook')->name('restart_webhook');
        });
    });
});