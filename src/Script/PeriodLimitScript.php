<?php

declare(strict_types=1);

namespace AtlasChan\Hyperf\PeriodLimit\Script;

use Hyperf\Redis\Lua\Script;

class PeriodLimitScript extends Script
{
    public function getScript(): string
    {
        return <<<'LUA'
local limit = tonumber(ARGV[1])
local window = tonumber(ARGV[2])
local current = redis.call("INCRBY", KEYS[1], 1)
if current == 1 then
    redis.call("expire", KEYS[1], window)
    return 1
elseif current < limit then
    return 1
elseif current == limit then
    return 2
else
    return 0
end
LUA;
    }

    public function format($data)
    {
        if (is_numeric($data)) {
            return $data;
        }
        return null;
    }
}