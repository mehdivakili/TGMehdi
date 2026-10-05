<?php

// Run with: php tests/chat_session.php (after composer install).
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Helpers/translations.php';

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Redis;
use TGMehdi\Routing\BotRout;
use TGMehdi\Routing\Middlewares\AuthMiddleware;
use TGMehdi\TelegramBot;

function checkSession($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

class SessionTestBot extends TelegramBot
{
    public int $databaseReads = 0;

    public function chat($rewrite = false)
    {
        $this->databaseReads++;
        return (object) ['status' => '.start.'];
    }
}

$app = new Container();
Container::setInstance($app);
Facade::setFacadeApplication($app);
$settings = [
    'token' => 'fixture-token', 'cache_optimization' => true,
    'allowed_chats' => ['private'], 'update_types' => ['message', 'callback_query'],
];
$app->instance('config', new Repository(['tgmehdi' => ['bots' => [
    'expire' => $settings,
    'other' => $settings,
    'shared' => $settings + ['shared' => 'expire'],
    'stateless' => $settings + ['shared' => 'nothing'],
]]]));
$app->instance(BotRout::class, new BotRout());
$redis = new class {
    public array $hashes = [];
    public array $reads = [];
    public array $writes = [];

    public function hgetall($key)
    {
        $this->reads[] = $key;
        return $this->hashes[$key] ?? [];
    }

    public function hmset($key, $data)
    {
        $this->writes[] = $key;
        $this->hashes[$key] = array_replace($this->hashes[$key] ?? [], array_map('strval', $data));
    }
};
Redis::swap($redis);

function sessionRequest(TelegramBot $bot, $name, $chatId = 123, $callback = false)
{
    $bot->switch_bot($name);
    $message = ['message_id' => 1, 'text' => 'menu', 'chat' => ['id' => $chatId, 'type' => 'private']];
    $bot->data_init($callback ? ['callback_query' => ['data' => 'menu', 'message' => $message]] : ['message' => $message]);
}

$saved = [
    'user_id' => '42', 'expire_auth_signature' => hash('sha256', 'fixture-credentials'),
    'status' => '.main.', 'save_date' => (string) now()->timestamp,
];
$redis->hashes['expire_chat_123.data'] = $saved;
$bot = new SessionTestBot();
$bot->switch_bot('expire');
checkSession($redis->reads === [], 'Switching bots must wait until the chat ID is known before reading Redis');
sessionRequest($bot, 'expire');
checkSession($bot->chat_data() === $saved, 'A new request must reload the whole saved session');
checkSession($bot->chat_status === '.main.', 'Cached navigation state must be restored before routing');
checkSession((new AuthMiddleware())->handle($bot), 'A saved login must still pass authentication');
checkSession($bot->databaseReads === 0, 'A recent cached session must avoid loading stale database state');
checkSession($redis->reads === ['expire_chat_123.data'], 'Session data must be loaded only once per request');

$bot->chat_status = '.account.';
$bot->save_chat_state();
sessionRequest($bot, 'expire', callback: true);
checkSession($bot->chat_status === '.account.', 'Callback requests must reload the last saved navigation state');
checkSession($bot->chat_data('expire_auth_signature') === $saved['expire_auth_signature'], 'The login signature must survive separate requests');
checkSession($bot->chat_data('user_id') === '42', 'The account ID must survive separate requests');

$other = array_replace($saved, ['user_id' => '99', 'status' => '.other.']);
$redis->hashes['other_chat_123.data'] = $other;
sessionRequest($bot, 'other');
checkSession($bot->chat_data() === $other, 'Switching bots must not retain another bot session');
sessionRequest($bot, 'shared');
checkSession($bot->chat_data('user_id') === '42', 'Shared bots must reload the shared session namespace');

sessionRequest($bot, 'expire', 456);
checkSession($bot->chat_data() === [], 'A chat without cached data must not inherit another chat session');
checkSession($bot->chat_status === '.start.', 'A cache miss must fall back to the database navigation state');
checkSession(!(new AuthMiddleware())->handle($bot), 'A cache miss must not inherit authentication');

$reads = count($redis->reads);
$writes = count($redis->writes);
sessionRequest($bot, 'stateless');
$bot->chat_data('user_id', 7);
$bot->save_chat_state();
checkSession(count($redis->reads) === $reads && count($redis->writes) === $writes, 'Stateless bots must not read or write Redis sessions');
sessionRequest($bot, 'stateless');
checkSession($bot->chat_data() === [], 'Stateless bots must clear their in-memory session between requests');

echo "Chat session regression checks passed.\n";
