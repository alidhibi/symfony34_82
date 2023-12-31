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

use Symfony\Component\Cache\Adapter\RedisAdapter;

abstract class AbstractRedisAdapterTest extends AdapterTestCase
{
    protected $skippedTests = [
        'testExpiration' => 'Testing expiration slows down the test suite',
        'testHasItemReturnsFalseWhenDeferredItemIsExpired' => 'Testing expiration slows down the test suite',
        'testDefaultLifeTime' => 'Testing expiration slows down the test suite',
    ];

    protected static $redis;

    public function createCachePool($defaultLifetime = 0)
    {
        return new RedisAdapter(self::$redis, str_replace('\\', '.', __CLASS__), $defaultLifetime);
    }

    public static function setUpBeforeClass(): void
    {
        if (!\extension_loaded('redis')) {
            self::markTestSkipped('Extension redis required.');
        }

        try {
            (new \Redis())->connect(getenv('REDIS_HOST'));
        } catch (\Exception $exception) {
            self::markTestSkipped($exception->getMessage());
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$redis = null;
    }
}
