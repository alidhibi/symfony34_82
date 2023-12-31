<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Lock\Tests\Store;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Store\CombinedStore;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\StoreInterface;
use Symfony\Component\Lock\Strategy\StrategyInterface;
use Symfony\Component\Lock\Strategy\UnanimousStrategy;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CombinedStoreTest extends AbstractStoreTest
{
    use ExpiringStoreTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function getClockDelay(): int
    {
        return 250000;
    }

    /**
     * {@inheritdoc}
     */
    protected function getStore(): \Symfony\Component\Lock\Store\CombinedStore
    {
        $redis = new \Predis\Client('tcp://'.getenv('REDIS_HOST').':6379');
        try {
            $redis->connect();
        } catch (\Exception $exception) {
            self::markTestSkipped($exception->getMessage());
        }

        return new CombinedStore([new RedisStore($redis)], new UnanimousStrategy());
    }

    /** @var MockObject */
    private $strategy;

    /** @var MockObject */
    private $store1;

    /** @var MockObject */
    private $store2;

    private \Symfony\Component\Lock\Store\CombinedStore $store;

    protected function setUp()
    {
        $this->strategy = $this->getMockBuilder(StrategyInterface::class)->getMock();
        $this->store1 = $this->getMockBuilder(StoreInterface::class)->getMock();
        $this->store2 = $this->getMockBuilder(StoreInterface::class)->getMock();

        $this->store = new CombinedStore([$this->store1, $this->store2], $this->strategy);
    }

    public function testSaveThrowsExceptionOnFailure(): void
    {
        $this->expectException(\Symfony\Component\Lock\Exception\LockConflictedException::class);
        $key = new Key(uniqid(__METHOD__, true));

        $this->store1
            ->expects($this->once())
            ->method('save')
            ->with($key)
            ->willThrowException(new LockConflictedException());
        $this->store2
            ->expects($this->once())
            ->method('save')
            ->with($key)
            ->willThrowException(new LockConflictedException());

        $this->strategy
            ->expects($this->any())
            ->method('canBeMet')
            ->willReturn(true);
        $this->strategy
            ->expects($this->any())
            ->method('isMet')
            ->willReturn(false);

        $this->store->save($key);
    }

    public function testSaveCleanupOnFailure(): void
    {
        $key = new Key(uniqid(__METHOD__, true));

        $this->store1
            ->expects($this->once())
            ->method('save')
            ->with($key)
            ->willThrowException(new LockConflictedException());
        $this->store2
            ->expects($this->once())
            ->method('save')
            ->with($key)
            ->willThrowException(new LockConflictedException());

        $this->store1
            ->expects($this->once())
            ->method('delete');
        $this->store2
            ->expects($this->once())
            ->method('delete');

        $this->strategy
            ->expects($this->any())
            ->method('canBeMet')
            ->willReturn(true);
        $this->strategy
            ->expects($this->any())
            ->method('isMet')
            ->willReturn(false);

        try {
            $this->store->save($key);
        } catch (LockConflictedException $lockConflictedException) {
            // Catch the exception given this is not what we want to assert in this tests
        }
    }

    public function testSaveAbortWhenStrategyCantBeMet(): void
    {
        $key = new Key(uniqid(__METHOD__, true));

        $this->store1
            ->expects($this->once())
            ->method('save')
            ->with($key)
            ->willThrowException(new LockConflictedException());
        $this->store2
            ->expects($this->never())
            ->method('save');

        $this->strategy
            ->expects($this->once())
            ->method('canBeMet')
            ->willReturn(false);
        $this->strategy
            ->expects($this->any())
            ->method('isMet')
            ->willReturn(false);

        try {
            $this->store->save($key);
        } catch (LockConflictedException $lockConflictedException) {
            // Catch the exception given this is not what we want to assert in this tests
        }
    }

    public function testputOffExpirationThrowsExceptionOnFailure(): void
    {
        $this->expectException(\Symfony\Component\Lock\Exception\LockConflictedException::class);
        $key = new Key(uniqid(__METHOD__, true));
        $ttl = random_int(1, 10);

        $this->store1
            ->expects($this->once())
            ->method('putOffExpiration')
            ->with($key, $this->lessThanOrEqual($ttl))
            ->willThrowException(new LockConflictedException());
        $this->store2
            ->expects($this->once())
            ->method('putOffExpiration')
            ->with($key, $this->lessThanOrEqual($ttl))
            ->willThrowException(new LockConflictedException());

        $this->strategy
            ->expects($this->any())
            ->method('canBeMet')
            ->willReturn(true);
        $this->strategy
            ->expects($this->any())
            ->method('isMet')
            ->willReturn(false);

        $this->store->putOffExpiration($key, $ttl);
    }

    public function testputOffExpirationCleanupOnFailure(): void
    {
        $key = new Key(uniqid(__METHOD__, true));
        $ttl = random_int(1, 10);

        $this->store1
            ->expects($this->once())
            ->method('putOffExpiration')
            ->with($key, $this->lessThanOrEqual($ttl))
            ->willThrowException(new LockConflictedException());
        $this->store2
            ->expects($this->once())
            ->method('putOffExpiration')
            ->with($key, $this->lessThanOrEqual($ttl))
            ->willThrowException(new LockConflictedException());

        $this->store1
            ->expects($this->once())
            ->method('delete');
        $this->store2
            ->expects($this->once())
            ->method('delete');

        $this->strategy
            ->expects($this->any())
            ->method('canBeMet')
            ->willReturn(true);
        $this->strategy
            ->expects($this->any())
            ->method('isMet')
            ->willReturn(false);

        try {
            $this->store->putOffExpiration($key, $ttl);
        } catch (LockConflictedException $lockConflictedException) {
            // Catch the exception given this is not what we want to assert in this tests
        }
    }

    public function testputOffExpirationAbortWhenStrategyCantBeMet(): void
    {
        $key = new Key(uniqid(__METHOD__, true));
        $ttl = random_int(1, 10);

        $this->store1
            ->expects($this->once())
            ->method('putOffExpiration')
            ->with($key, $this->lessThanOrEqual($ttl))
            ->willThrowException(new LockConflictedException());
        $this->store2
            ->expects($this->never())
            ->method('putOffExpiration');

        $this->strategy
            ->expects($this->once())
            ->method('canBeMet')
            ->willReturn(false);
        $this->strategy
            ->expects($this->any())
            ->method('isMet')
            ->willReturn(false);

        try {
            $this->store->putOffExpiration($key, $ttl);
        } catch (LockConflictedException $lockConflictedException) {
            // Catch the exception given this is not what we want to assert in this tests
        }
    }

    public function testPutOffExpirationIgnoreNonExpiringStorage(): void
    {
        $store1 = $this->getMockBuilder(StoreInterface::class)->getMock();
        $store2 = $this->getMockBuilder(StoreInterface::class)->getMock();

        $store = new CombinedStore([$store1, $store2], $this->strategy);

        $key = new Key(uniqid(__METHOD__, true));
        $ttl = random_int(1, 10);

        $this->strategy
            ->expects($this->any())
            ->method('canBeMet')
            ->willReturn(true);
        $this->strategy
            ->expects($this->once())
            ->method('isMet')
            ->with(2, 2)
            ->willReturn(true);

        $store->putOffExpiration($key, $ttl);
    }

    public function testExistsDontAskToEveryBody(): void
    {
        $key = new Key(uniqid(__METHOD__, true));

        $this->store1
            ->expects($this->any())
            ->method('exists')
            ->with($key)
            ->willReturn(false);
        $this->store2
            ->expects($this->never())
            ->method('exists');

        $this->strategy
            ->expects($this->any())
            ->method('canBeMet')
            ->willReturn(true);
        $this->strategy
            ->expects($this->once())
            ->method('isMet')
            ->willReturn(true);

        $this->assertTrue($this->store->exists($key));
    }

    public function testExistsAbortWhenStrategyCantBeMet(): void
    {
        $key = new Key(uniqid(__METHOD__, true));

        $this->store1
            ->expects($this->any())
            ->method('exists')
            ->with($key)
            ->willReturn(false);
        $this->store2
            ->expects($this->never())
            ->method('exists');

        $this->strategy
            ->expects($this->once())
            ->method('canBeMet')
            ->willReturn(false);
        $this->strategy
            ->expects($this->once())
            ->method('isMet')
            ->willReturn(false);

        $this->assertFalse($this->store->exists($key));
    }

    public function testDeleteDontStopOnFailure(): void
    {
        $key = new Key(uniqid(__METHOD__, true));

        $this->store1
            ->expects($this->once())
            ->method('delete')
            ->with($key)
            ->willThrowException(new \Exception());
        $this->store2
            ->expects($this->once())
            ->method('delete')
            ->with($key);

        $this->store->delete($key);
    }
}
