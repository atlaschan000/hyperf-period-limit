<?php

namespace AtlasChanTest\PeriodLimit;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Config\Config;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Pool\Channel;
use Hyperf\Pool\LowFrequencyInterface;
use Hyperf\Pool\PoolOption;
use Hyperf\Redis\Frequency;
use Hyperf\Redis\Pool\PoolFactory;
use Hyperf\Redis\Pool\RedisPool;
use Hyperf\Redis\Redis;
use Hyperf\Di\Container;
use Hyperf\Utils\ApplicationContext;
use Mockery;

class ContainerStub
{
    public static function mockContainer()
    {
        $container = Mockery::mock(Container::class);
        $container->shouldReceive('get')->once()->with(ConfigInterface::class)->andReturn(new Config([
            'redis' => [
                'default' => [
                    'host' => 'localhost',
                    'auth' => null,
                    'port' => 6379,
                    'db' => 0,
                    'pool' => [
                        'min_connections' => 1,
                        'max_connections' => 30,
                        'connect_timeout' => 10.0,
                        'wait_timeout' => 3.0,
                        'heartbeat' => -1,
                        'max_idle_time' => 60,
                    ],
                ],
            ],
        ]));
        $pool      = new RedisPool($container, 'default');
        $frequency = Mockery::mock(LowFrequencyInterface::class);
        $frequency->shouldReceive('isLowFrequency')->andReturn(false);
        $container->shouldReceive('make')->with(Frequency::class, Mockery::any())->andReturn($frequency);
        $container->shouldReceive('make')->with(RedisPool::class, ['name' => 'default'])->andReturn($pool);
        $container->shouldReceive('make')->with(Channel::class, ['size' => 30])->andReturn(new Channel(30));
        $container->shouldReceive('make')->with(PoolOption::class, Mockery::any())->andReturnUsing(function ($class, $args) {
            return new PoolOption(...array_values($args));
        });
        $container->shouldReceive('has')->with(\Redis::class)->andReturn(true);
        $container->shouldReceive('get')->with(\Redis::class)->andReturn(value(function () use ($container) {
            $factory = new PoolFactory($container);
            return new Redis($factory);
        }));
        $container->shouldReceive('has')->with(StdoutLoggerInterface::class)->andReturn(true);
        $container->shouldReceive('get')->with(StdoutLoggerInterface::class)->andReturn(value(function () {
            return Mockery::mock(StdoutLoggerInterface::class);
        }));

        ApplicationContext::setContainer($container);
        return $container;
    }
}