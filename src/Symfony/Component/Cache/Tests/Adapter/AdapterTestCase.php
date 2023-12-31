<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Adapter;

use Cache\IntegrationTests\CachePoolTest;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\PruneableInterface;

abstract class AdapterTestCase extends CachePoolTest
{
    public $skippedTests;
    protected function setUp()
    {
        parent::setUp();

        if (!\array_key_exists('testDeferredSaveWithoutCommit', $this->skippedTests) && \defined('HHVM_VERSION')) {
            $this->skippedTests['testDeferredSaveWithoutCommit'] = 'Destructors are called late on HHVM.';
        }

        if (!\array_key_exists('testPrune', $this->skippedTests) && !$this->createCachePool() instanceof PruneableInterface) {
            $this->skippedTests['testPrune'] = 'Not a pruneable cache pool.';
        }
    }

    public function testDefaultLifeTime(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $cache = $this->createCachePool(2);

        $item = $cache->getItem('key.dlt');
        $item->set('value');

        $cache->save($item);
        sleep(1);

        $item = $cache->getItem('key.dlt');
        $this->assertTrue($item->isHit());

        sleep(2);
        $item = $cache->getItem('key.dlt');
        $this->assertFalse($item->isHit());
    }

    public function testExpiration(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $cache = $this->createCachePool();
        $cache->save($cache->getItem('k1')->set('v1')->expiresAfter(2));
        $cache->save($cache->getItem('k2')->set('v2')->expiresAfter(366 * 86400));

        sleep(3);
        $item = $cache->getItem('k1');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit() is false.");

        $item = $cache->getItem('k2');
        $this->assertTrue($item->isHit());
        $this->assertSame('v2', $item->get());
    }

    public function testNotUnserializable(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $cache = $this->createCachePool();

        $item = $cache->getItem('foo');
        $cache->save($item->set(new NotUnserializable()));

        $item = $cache->getItem('foo');
        $this->assertFalse($item->isHit());


        $cache->save($item->set(new NotUnserializable()));


        $this->assertFalse($item->isHit());
    }

    public function testPrune(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        if (!method_exists($this, 'isPruned')) {
            $this->fail('Test classes for pruneable caches must implement `isPruned($cache, $name)` method.');
        }

        /** @var PruneableInterface|CacheItemPoolInterface $cache */
        $cache = $this->createCachePool();

        $doSet = static function ($name, $value, \DateInterval $expiresAfter = null) use ($cache) : void {
            $item = $cache->getItem($name);
            $item->set($value);
            if ($expiresAfter instanceof \DateInterval) {
                $item->expiresAfter($expiresAfter);
            }
            $cache->save($item);
        };

        $doSet('foo', 'foo-val', new \DateInterval('PT05S'));
        $doSet('bar', 'bar-val', new \DateInterval('PT10S'));
        $doSet('baz', 'baz-val', new \DateInterval('PT15S'));
        $doSet('qux', 'qux-val', new \DateInterval('PT20S'));

        sleep(30);
        $cache->prune();
        $this->assertTrue($this->isPruned($cache, 'foo'));
        $this->assertTrue($this->isPruned($cache, 'bar'));
        $this->assertTrue($this->isPruned($cache, 'baz'));
        $this->assertTrue($this->isPruned($cache, 'qux'));

        $doSet('foo', 'foo-val');
        $doSet('bar', 'bar-val', new \DateInterval('PT20S'));
        $doSet('baz', 'baz-val', new \DateInterval('PT40S'));
        $doSet('qux', 'qux-val', new \DateInterval('PT80S'));

        $cache->prune();
        $this->assertFalse($this->isPruned($cache, 'foo'));
        $this->assertFalse($this->isPruned($cache, 'bar'));
        $this->assertFalse($this->isPruned($cache, 'baz'));
        $this->assertFalse($this->isPruned($cache, 'qux'));

        sleep(30);
        $cache->prune();
        $this->assertFalse($this->isPruned($cache, 'foo'));
        $this->assertTrue($this->isPruned($cache, 'bar'));
        $this->assertFalse($this->isPruned($cache, 'baz'));
        $this->assertFalse($this->isPruned($cache, 'qux'));

        sleep(30);
        $cache->prune();
        $this->assertFalse($this->isPruned($cache, 'foo'));
        $this->assertTrue($this->isPruned($cache, 'baz'));
        $this->assertFalse($this->isPruned($cache, 'qux'));

        sleep(30);
        $cache->prune();
        $this->assertFalse($this->isPruned($cache, 'foo'));
        $this->assertTrue($this->isPruned($cache, 'qux'));
    }
}

class NotUnserializable implements \Serializable
{
    public function serialize(): string
    {
        return serialize(123);
    }

    public function unserialize($ser): never
    {
        throw new \Exception(__CLASS__);
    }
}
