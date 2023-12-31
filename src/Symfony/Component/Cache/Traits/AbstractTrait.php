<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Traits;

use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\CacheItem;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
trait AbstractTrait
{
    use LoggerAwareTrait;

    private $namespace;

    private $namespaceVersion = '';

    private $versioningIsEnabled = false;

    private $deferred = [];

    /**
     * @var int|null The maximum length to enforce for identifiers or null when no limit applies
     */
    protected $maxIdLength;

    /**
     * Fetches several cache items.
     *
     * @param array $ids The cache identifiers to fetch
     *
     * @return array|\Traversable The corresponding values found in the cache
     */
    abstract protected function doFetch(array $ids);

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $id The identifier for which to check existence
     *
     * @return bool True if item exists in the cache, false otherwise
     */
    abstract protected function doHave($id);

    /**
     * Deletes all items in the pool.
     *
     * @param string $namespace The prefix used for all identifiers managed by this pool
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    abstract protected function doClear($namespace);

    /**
     * Removes multiple items from the pool.
     *
     * @param array $ids An array of identifiers that should be removed from the pool
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    abstract protected function doDelete(array $ids);

    /**
     * Persists several cache items immediately.
     *
     * @param array $values   The values to cache, indexed by their cache identifier
     * @param int   $lifetime The lifetime of the cached values, 0 for persisting until manual cleaning
     *
     * @return array|bool The identifiers that failed to be cached or a boolean stating if caching succeeded or not
     */
    abstract protected function doSave(array $values, $lifetime);

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        $id = $this->getId($key);

        if (isset($this->deferred[$key])) {
            $this->commit();
        }

        try {
            return $this->doHave($id);
        } catch (\Exception $exception) {
            CacheItem::log('Failed to check if key "{key}" is cached', $this->logger, ['key' => $key, 'exception' => $exception]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->deferred = [];
        if ($cleared = $this->versioningIsEnabled) {
            $namespaceVersion = substr_replace(base64_encode(pack('V', mt_rand())), static::NS_SEPARATOR, 5);
            try {
                $cleared = $this->doSave([static::NS_SEPARATOR.$this->namespace => $namespaceVersion], 0);
            } catch (\Exception $e) {
                $cleared = false;
            }

            if ($cleared = true === $cleared || [] === $cleared) {
                $this->namespaceVersion = $namespaceVersion;
            }
        }

        try {
            return $this->doClear($this->namespace) || $cleared;
        } catch (\Exception $exception) {
            CacheItem::log('Failed to clear the cache', $this->logger, ['exception' => $exception]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->deleteItems([$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $ids = [];

        foreach ($keys as $key) {
            $ids[$key] = $this->getId($key);
            unset($this->deferred[$key]);
        }

        try {
            if ($this->doDelete($ids)) {
                return true;
            }
        } catch (\Exception $exception) {
        }

        $ok = true;

        // When bulk-delete failed, retry each item individually
        foreach ($ids as $key => $id) {
            try {
                $e = null;
                if ($this->doDelete([$id])) {
                    continue;
                }
            } catch (\Exception $e) {
            }

            CacheItem::log('Failed to delete key "{key}"', $this->logger, ['key' => $key, 'exception' => $e]);
            $ok = false;
        }

        return $ok;
    }

    /**
     * Enables/disables versioning of items.
     *
     * When versioning is enabled, clearing the cache is atomic and doesn't require listing existing keys to proceed,
     * but old keys may need garbage collection and extra round-trips to the back-end are required.
     *
     * Calling this method also clears the memoized namespace version and thus forces a resynchonization of it.
     *
     * @param bool $enable
     *
     * @return bool the previous state of versioning
     */
    public function enableVersioning($enable = true)
    {
        $wasEnabled = $this->versioningIsEnabled;
        $this->versioningIsEnabled = (bool) $enable;
        $this->namespaceVersion = '';

        return $wasEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        if ($this->deferred) {
            $this->commit();
        }

        $this->namespaceVersion = '';
    }

    /**
     * Like the native unserialize() function but throws an exception if anything goes wrong.
     *
     * @param string $value
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected static function unserialize($value)
    {
        if ('b:0;' === $value) {
            return false;
        }

        $unserializeCallbackHandler = ini_set('unserialize_callback_func', __CLASS__.'::handleUnserializeCallback');
        try {
            if (false !== $value = unserialize($value)) {
                return $value;
            }

            throw new \DomainException('Failed to unserialize cached value.');
        } catch (\Error $error) {
            throw new \ErrorException($error->getMessage(), $error->getCode(), \E_ERROR, $error->getFile(), $error->getLine());
        } finally {
            ini_set('unserialize_callback_func', $unserializeCallbackHandler);
        }
    }

    private function getId(string $key)
    {
        CacheItem::validateKey($key);

        if ($this->versioningIsEnabled && '' === $this->namespaceVersion) {
            $this->namespaceVersion = '1'.static::NS_SEPARATOR;
            try {
                foreach ($this->doFetch([static::NS_SEPARATOR.$this->namespace]) as $v) {
                    $this->namespaceVersion = $v;
                }

                if ('1'.static::NS_SEPARATOR === $this->namespaceVersion) {
                    $this->namespaceVersion = substr_replace(base64_encode(pack('V', time())), static::NS_SEPARATOR, 5);
                    $this->doSave([static::NS_SEPARATOR.$this->namespace => $this->namespaceVersion], 0);
                }
            } catch (\Exception $e) {
            }
        }

        if (null === $this->maxIdLength) {
            return $this->namespace.$this->namespaceVersion.$key;
        }

        if (\strlen($id = $this->namespace.$this->namespaceVersion.$key) > $this->maxIdLength) {
            $id = $this->namespace.$this->namespaceVersion.substr_replace(base64_encode(hash('sha256', $key, true)), static::NS_SEPARATOR, -(\strlen($this->namespaceVersion) + 22));
        }

        return $id;
    }

    /**
     * @internal
     */
    public static function handleUnserializeCallback(string $class): never
    {
        throw new \DomainException('Class not found: '.$class);
    }
}
