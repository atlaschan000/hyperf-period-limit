<?php

declare(strict_types=1);

namespace AtlasChanTest\PeriodLimit;

use AtlasChan\Hyperf\PeriodLimit\PeriodLimit;
use AtlasChan\Hyperf\PeriodLimit\Script\PeriodLimitScript;
use PHPUnit\Framework\TestCase;

class PeriodLimitTest extends TestCase{

    public function testPeriodLimit() {
        $container = ContainerStub::mockContainer();
        $limit = new PeriodLimit(300,5,$container,"pl_",true);
        $ret = $limit->take("login_test");
        $this->assertEquals(2,$ret);
    }
}