<?php

namespace TGMehdi\Queue;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\RedisQueue;
use TGMehdi\TelegramBot;

class TGConnector implements ConnectorInterface
{

    protected TelegramBot $bot;
    /**
     * @var mixed|null
     */
    protected $connection;

    public function __construct(TelegramBot $bot, $connection = null)
    {
        $this->bot = $bot;
        $this->connection = $connection;
    }

    public function connect(array $config)
    {
        return new TGQueue(
            $this->bot, $config['queue'],
            $config['connection'] ?? $this->connection,
        );
    }
}