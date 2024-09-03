<?php


namespace TGMehdi;


use TGMehdi\Routing\Inputs\CallbackInput;
use TGMehdi\Routing\Inputs\MessageInput;
use TGMehdi\Routing\Inputs\UpdateInput;
use TGMehdi\Routing\Middlewares\AnyMiddleware;
use TGMehdi\Routing\Middlewares\AuthMiddleware;
use TGMehdi\Routing\Middlewares\ExceptMiddleware;
use TGMehdi\Routing\Middlewares\MustJoinChannels;
use TGMehdi\Routing\Middlewares\OnlyMiddleware;

class BotKernel
{
    public static $input_parsers = [
        'message' => MessageInput::class,
        'edited_message' => MessageInput::class,
        'callback_query' => CallbackInput::class,
        'my_chat_member' => UpdateInput::class,
        'chat_member' => UpdateInput::class,
        'chat_join_request' => UpdateInput::class,
        'chat_boost' => UpdateInput::class,
        'removed_chat_boost' => UpdateInput::class,
    ];
    public static $middlewares = [
        'only' => OnlyMiddleware::class,
        'except' => ExceptMiddleware::class,
        'any' => AnyMiddleware::class,
        'auth' => AuthMiddleware::class,
        'join' => MustJoinChannels::class,
    ];
}
