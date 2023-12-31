<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Stopwatch\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * StopwatchEventTest.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @group time-sensitive
 */
class StopwatchEventTest extends TestCase
{
    final const DELTA = 37;

    public function testGetOrigin(): void
    {
        $event = new StopwatchEvent(12);
        $this->assertEquals(12, $event->getOrigin());
    }

    public function testGetCategory(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $this->assertEquals('default', $event->getCategory());

        $event = new StopwatchEvent(microtime(true) * 1000, 'cat');
        $this->assertEquals('cat', $event->getCategory());
    }

    public function testGetPeriods(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $this->assertEquals([], $event->getPeriods());

        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        $event->stop();
        $this->assertCount(1, $event->getPeriods());

        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        $event->stop();
        $event->start();
        $event->stop();
        $this->assertCount(2, $event->getPeriods());
    }

    public function testLap(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        $event->lap();
        $event->stop();
        $this->assertCount(2, $event->getPeriods());
    }

    public function testDuration(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        usleep(200000);
        $event->stop();
        $this->assertEqualsWithDelta(200, $event->getDuration(), self::DELTA);

        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        usleep(100000);
        $event->stop();
        usleep(50000);
        $event->start();
        usleep(100000);
        $event->stop();
        $this->assertEqualsWithDelta(200, $event->getDuration(), self::DELTA);
    }

    public function testDurationBeforeStop(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        usleep(200000);
        $this->assertEqualsWithDelta(200, $event->getDuration(), self::DELTA);

        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        usleep(100000);
        $event->stop();
        usleep(50000);
        $event->start();
        $this->assertEqualsWithDelta(100, $event->getDuration(), self::DELTA);
        usleep(100000);
        $this->assertEqualsWithDelta(200, $event->getDuration(), self::DELTA);
    }

    public function testDurationWithMultipleStarts(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        usleep(100000);
        $event->start();
        usleep(100000);
        $this->assertEqualsWithDelta(300, $event->getDuration(), self::DELTA);
        $event->stop();
        $this->assertEqualsWithDelta(300, $event->getDuration(), self::DELTA);
        usleep(100000);
        $this->assertEqualsWithDelta(400, $event->getDuration(), self::DELTA);
        $event->stop();
        $this->assertEqualsWithDelta(400, $event->getDuration(), self::DELTA);
    }

    public function testStopWithoutStart(): void
    {
        $this->expectException('LogicException');
        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->stop();
    }

    public function testIsStarted(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        $this->assertTrue($event->isStarted());
    }

    public function testIsNotStarted(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $this->assertFalse($event->isStarted());
    }

    public function testEnsureStopped(): void
    {
        // this also test overlap between two periods
        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        usleep(100000);
        $event->start();
        usleep(100000);
        $event->ensureStopped();
        $this->assertEqualsWithDelta(300, $event->getDuration(), self::DELTA);
    }

    public function testStartTime(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $this->assertLessThanOrEqual(0.5, $event->getStartTime());

        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        $event->stop();
        $this->assertLessThanOrEqual(1, $event->getStartTime());

        $event = new StopwatchEvent(microtime(true) * 1000);
        $event->start();
        usleep(100000);
        $event->stop();
        $this->assertEqualsWithDelta(0, $event->getStartTime(), self::DELTA);
    }

    public function testStartTimeWhenStartedLater(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        usleep(100000);
        $this->assertLessThanOrEqual(0.5, $event->getStartTime());

        $event = new StopwatchEvent(microtime(true) * 1000);
        usleep(100000);
        $event->start();
        $event->stop();
        $this->assertLessThanOrEqual(101, $event->getStartTime());

        $event = new StopwatchEvent(microtime(true) * 1000);
        usleep(100000);
        $event->start();
        usleep(100000);
        $this->assertEqualsWithDelta(100, $event->getStartTime(), self::DELTA);
        $event->stop();
        $this->assertEqualsWithDelta(100, $event->getStartTime(), self::DELTA);
    }

    public function testInvalidOriginThrowsAnException(): void
    {
        $this->expectException('InvalidArgumentException');
        new StopwatchEvent('abc');
    }

    public function testHumanRepresentation(): void
    {
        $event = new StopwatchEvent(microtime(true) * 1000);
        $this->assertEquals('default: 0.00 MiB - 0 ms', (string) $event);
        $event->start();
        $event->stop();
        $this->assertEquals(1, preg_match('/default: [0-9\.]+ MiB - \d+ ms/', (string) $event));

        $event = new StopwatchEvent(microtime(true) * 1000, 'foo');
        $this->assertEquals('foo: 0.00 MiB - 0 ms', (string) $event);
    }
}
