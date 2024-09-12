<?php

namespace TGMehdi\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class RedisThrottle
{
    private mixed $allow;
    private mixed $seconds;

    public function __construct($key, $allow, $seconds)
    {
        $this->key = $key;
        $this->allow = $allow;
        $this->seconds = $seconds;
    }

    public function handle(object $job, Closure $next): void
    {
        Redis::throttle($this->key)
            ->block($this->seconds)->allow($this->allow)->every($this->seconds)
            ->then(function () use ($job, $next) {
                $next($job);
            }, function () use ($job) {
                $job->release($this->seconds);
            });
    }
}
