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

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @requires extension redis
 */
class RedisStoreTest extends AbstractRedisStoreTest
{
    public static function setUpBeforeClass(): void
    {
        try {
            (new \Redis())->connect(getenv('REDIS_HOST'));
        } catch (\Exception $exception) {
            self::markTestSkipped($exception->getMessage());
        }
    }

    protected function getRedisConnection(): \Redis
    {
        $redis = new \Redis();
        $redis->connect(getenv('REDIS_HOST'));

        return $redis;
    }
}
