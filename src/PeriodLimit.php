<?php

declare(strict_types=1);

namespace AtlasChan\Hyperf\PeriodLimit;

use AtlasChan\Hyperf\PeriodLimit\Script\PeriodLimitScript;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;

class PeriodLimit
{
    // Unknown means not initialized state.
    const   UNKNOWN = 0;
	// Allowed means allowed state.
    const   ALLOWED = 1;
	// HitQuota means this request exactly hit the quota.
	const   HIT_QUOTA = 2;
	// OverQuota means passed the quota.
	const   OVER_QUOTA = 3;

    const   INTERNAL_OVER_QUOTA = 0;
    const   INTERNAL_ALLOWED   = 1;
    const   INTERNAL_HIT_QUOTA  = 2;

    const ZONE_DIFF = 3600 * 8;

    private $period;
    private $quota;
    private $keyPrefix;
    private $container;
    private $align;

    public function __construct(int $period,$quota,ContainerInterface $container,string $keyPrefix,bool $align)
    {
        $this->period = $period;
        $this->quota = $quota;
        $this->container = $container;
        $this->keyPrefix = $keyPrefix;
        $this->align = $align;
    }

    public function take(string $key) : int {
        $script = new PeriodLimitScript($this->container);
        try {
            $resp = $script->eval([$this->keyPrefix . $key, (string)$this->quota, (string)$this->calcExpireSeconds()]);
            if(!is_numeric($resp)){
                return self::UNKNOWN;
            }
            switch ($resp){
                case self::INTERNAL_OVER_QUOTA:
                    return self::OVER_QUOTA;
                case self::INTERNAL_ALLOWED :
                    return self::ALLOWED;
                case self::INTERNAL_HIT_QUOTA:
                    return self::HIT_QUOTA;
                default:
                    return self::UNKNOWN;
            }
        }
        catch (\Throwable $throwable){
            return self::UNKNOWN;
        }

    }

    private function calcExpireSeconds() : int {
        if($this->align){
            $unix = time() + self::ZONE_DIFF;
            return $this->period - (int)$unix%$this->period;
        }
        return $this->period;
    }

}