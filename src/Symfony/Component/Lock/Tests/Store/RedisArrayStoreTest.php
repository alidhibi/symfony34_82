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
class RedisArrayStoreTest extends AbstractRedisStoreTest
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists('RedisArray')) {
            self::markTestSkipped('The RedisArray class is required.');
        }

        try {
            (new \Redis())->connect(getenv('REDIS_HOST'));
        } catch (\Exception $exception) {
            self::markTestSkipped($exception->getMessage());
        }
    }

    protected function getRedisConnection(): \RedisArray
    {
        return new \RedisArray([getenv('REDIS_HOST')]);
    }
}
