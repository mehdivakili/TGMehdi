<?php

use TGMehdi\Facades\BotRout;
use TGMehdi\Routing\BRH;
use TGMehdi\TelegramBot;
use TGMehdi\TGH;

// make bot Routs here
// some examples are shown below

BotRout::only(['text'], BRH::command('/start'), "hello");
BotRout::only(['text'], BRH::command('/time'), fn() => now()->format('H:i:s'));

BotRout::only(['text'], '/^my name is (\w+)$/', fn(TelegramBot $tg, $args) => 'hello ' . $args[0] . '!');
$keyboard = new \TGMehdi\Types\ReplyKeyboard(true);
$keyboard->newButton('back');

class Test
{
    function hello($name)
    {
        return 'Hello ' . $name;
    }
}

BotRout::only(['text'], BRH::command('/state'), TGH::goto_stat('main', TGH::with_keyboard('to main', $keyboard)));
BotRout::only(['text'], BRH::command('back'), TGH::goto_stat('back', 'backed'));
BotRout::only(['text'], '/^my name is (\w+) too$/', [new Test(), 'hello']);

$keyboard2 = new \TGMehdi\Types\InlineKeyboard();
$keyboard2->newButton('back', 'back');

BotRout::only(['text'], BRH::command('/callback'), TGH::with_keyboard('callback', $keyboard2));
BotRout::callback(BRH::command('back'), 'done');

class HelloController
{
    function hello(TelegramBot $tg, $world, $hello)
    {
        $tg->set_reply_message_id();
        $tg->send_text($hello . ' ' . $world);
    }
}

BotRout::only(['text'], ['/^(\w+) (\w+)$/', ['hello', 'world']], [HelloController::class, 'hello']);
BotRout::only(['text'], ['/^2 (\w+) (\w+)$/', ['hello', 'world']], function ($tg, $args) {
    return $args['hello'] . $args['world'];
});


