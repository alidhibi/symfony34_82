<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Lock\Store;

use Symfony\Component\Cache\Traits\RedisProxy;
use Symfony\Component\Lock\Exception\InvalidArgumentException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\NotSupportedException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\StoreInterface;

/**
 * RedisStore is a StoreInterface implementation using Redis as store engine.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class RedisStore implements StoreInterface
{
    use ExpiringStoreTrait;

    private $redis;

    private $initialTtl;

    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\ClientInterface $redisClient
     * @param float                                                    $initialTtl  the expiration delay of locks in seconds
     */
    public function __construct($redisClient, $initialTtl = 300.0)
    {
        if (!$redisClient instanceof \Redis && !$redisClient instanceof \RedisArray && !$redisClient instanceof \RedisCluster && !$redisClient instanceof \Predis\ClientInterface && !$redisClient instanceof RedisProxy) {
            throw new InvalidArgumentException(sprintf('"%s()" expects parameter 1 to be Redis, RedisArray, RedisCluster or Predis\ClientInterface, "%s" given.', __METHOD__, \is_object($redisClient) ? \get_class($redisClient) : \gettype($redisClient)));
        }

        if ($initialTtl <= 0) {
            throw new InvalidArgumentException(sprintf('"%s()" expects a strictly positive TTL. Got %d.', __METHOD__, $initialTtl));
        }

        $this->redis = $redisClient;
        $this->initialTtl = $initialTtl;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Key $key): void
    {
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("PEXPIRE", KEYS[1], ARGV[2])
            elseif redis.call("SET", KEYS[1], ARGV[1], "NX", "PX", ARGV[2]) then
                return 1
            else
                return 0
            end
        ';

        $key->reduceLifetime($this->initialTtl);
        if (!$this->evaluate($script, (string) $key, [$this->getToken($key), (int) ceil($this->initialTtl * 1000)])) {
            throw new LockConflictedException();
        }

        $this->checkNotExpired($key);
    }

    public function waitAndSave(Key $key): never
    {
        throw new NotSupportedException(sprintf('The store "%s" does not support blocking locks.', static::class));
    }

    /**
     * {@inheritdoc}
     */
    public function putOffExpiration(Key $key, $ttl): void
    {
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("PEXPIRE", KEYS[1], ARGV[2])
            else
                return 0
            end
        ';

        $key->reduceLifetime($ttl);
        if (!$this->evaluate($script, (string) $key, [$this->getToken($key), (int) ceil($ttl * 1000)])) {
            throw new LockConflictedException();
        }

        $this->checkNotExpired($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Key $key): void
    {
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        ';

        $this->evaluate($script, (string) $key, [$this->getToken($key)]);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Key $key): bool
    {
        return $this->redis->get((string) $key) === $this->getToken($key);
    }

    /**
     * Evaluates a script in the corresponding redis client.
     *
     *
     * @return mixed
     */
    private function evaluate(string $script, string $resource, array $args)
    {
        if ($this->redis instanceof \Redis || $this->redis instanceof \RedisCluster || $this->redis instanceof RedisProxy) {
            return $this->redis->eval($script, [$resource, ...$args], 1);
        }

        if ($this->redis instanceof \RedisArray) {
            return $this->redis->_instance($this->redis->_target($resource))->eval($script, [$resource, ...$args], 1);
        }
        return $this->redis->eval(...[$script, 1, $resource, ...$args]);
    }

    /**
     * Retrieves an unique token for the given key.
     *
     * @return string
     */
    private function getToken(Key $key)
    {
        if (!$key->hasState(__CLASS__)) {
            $token = base64_encode(random_bytes(32));
            $key->setState(__CLASS__, $token);
        }

        return $key->getState(__CLASS__);
    }
}
