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

use Symfony\Component\Lock\Store\MemcachedStore;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @requires extension memcached
 */
class MemcachedStoreTest extends AbstractStoreTest
{
    use ExpiringStoreTestTrait;

    public static function setUpBeforeClass(): void
    {
        $memcached = new \Memcached();
        $memcached->addServer(getenv('MEMCACHED_HOST'), 11211);
        $memcached->get('foo');

        $code = $memcached->getResultCode();

        if (\Memcached::RES_SUCCESS !== $code && \Memcached::RES_NOTFOUND !== $code) {
            self::markTestSkipped('Unable to connect to the memcache host');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getClockDelay(): int
    {
        return 1_000_000;
    }

    /**
     * {@inheritdoc}
     */
    protected function getStore(): \Symfony\Component\Lock\Store\MemcachedStore
    {
        $memcached = new \Memcached();
        $memcached->addServer(getenv('MEMCACHED_HOST'), 11211);

        return new MemcachedStore($memcached);
    }

    public function testAbortAfterExpiration(): void
    {
        $this->markTestSkipped('Memcached expects a TTL greater than 1 sec. Simulating a slow network is too hard');
    }
}
