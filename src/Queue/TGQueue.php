<?php

namespace TGMehdi\Queue;

use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use TGMehdi\TelegramBot;

class TGQueue extends Queue implements QueueContract, ClearableQueue
{
    public static array $queues = [];
    private string $connection;
    private string $default;
    private TelegramBot $bot;

    public function __construct(TelegramBot $bot, string $default, string $connection)
    {
        $this->connection = $connection;

        $this->default = $default;
        $this->bot = $bot;
    }

    /**
     * @return array
     */
    public function getQueues(): array
    {
        return self::$queues;
    }

    public function getQueue(string $name = null)
    {
        if (is_null($name))
            return $this->getQueue('default');
        if (!isset(self::$queues[$name]))
            self::$queues[$name] = [];
        return self::$queues[$name];
    }


    public function clear($queue)
    {
        self::$queues[$queue] = [];
    }

    public function size($queue = null)
    {
        return count($this->getQueue($queue));
    }

    public function push($job, $data = '', $queue = null)
    {
        $this->getQueue($queue);
        self::$queues[$queue][] = $job;
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->getQueue($queue);
        self::$queues[$queue][] = $payload;
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        $this->getQueue($queue);
        self::$queues[$queue][$delay] = $job;
    }

    public function pop($queue = null)
    {
        $this->getQueue($queue);
        return array_pop(self::$queues[$queue]);
    }
}