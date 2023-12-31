<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyAccess;

use Psr\Cache\CacheItemPoolInterface;

/**
 * A configurable builder to create a PropertyAccessor.
 *
 * @author Jérémie Augustin <jeremie.augustin@pixel-cookers.com>
 */
class PropertyAccessorBuilder
{
    private bool $magicCall = false;

    private bool $throwExceptionOnInvalidIndex = false;

    /**
     * @var CacheItemPoolInterface|null
     */
    private ?\Psr\Cache\CacheItemPoolInterface $cacheItemPool = null;

    /**
     * Enables the use of "__call" by the PropertyAccessor.
     *
     * @return $this
     */
    public function enableMagicCall(): static
    {
        $this->magicCall = true;

        return $this;
    }

    /**
     * Disables the use of "__call" by the PropertyAccessor.
     *
     * @return $this
     */
    public function disableMagicCall(): static
    {
        $this->magicCall = false;

        return $this;
    }

    /**
     * @return bool whether the use of "__call" by the PropertyAccessor is enabled
     */
    public function isMagicCallEnabled(): bool
    {
        return $this->magicCall;
    }

    /**
     * Enables exceptions when reading a non-existing index.
     *
     * This has no influence on writing non-existing indices with PropertyAccessorInterface::setValue()
     * which are always created on-the-fly.
     *
     * @return $this
     */
    public function enableExceptionOnInvalidIndex(): static
    {
        $this->throwExceptionOnInvalidIndex = true;

        return $this;
    }

    /**
     * Disables exceptions when reading a non-existing index.
     *
     * Instead, null is returned when calling PropertyAccessorInterface::getValue() on a non-existing index.
     *
     * @return $this
     */
    public function disableExceptionOnInvalidIndex(): static
    {
        $this->throwExceptionOnInvalidIndex = false;

        return $this;
    }

    /**
     * @return bool whether an exception is thrown or null is returned when reading a non-existing index
     */
    public function isExceptionOnInvalidIndexEnabled(): bool
    {
        return $this->throwExceptionOnInvalidIndex;
    }

    /**
     * Sets a cache system.
     *
     * @return PropertyAccessorBuilder The builder object
     */
    public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool = null): static
    {
        $this->cacheItemPool = $cacheItemPool;

        return $this;
    }

    /**
     * Gets the used cache system.
     *
     * @return CacheItemPoolInterface|null
     */
    public function getCacheItemPool(): ?\Psr\Cache\CacheItemPoolInterface
    {
        return $this->cacheItemPool;
    }

    /**
     * Builds and returns a new PropertyAccessor object.
     *
     * @return PropertyAccessorInterface The built PropertyAccessor
     */
    public function getPropertyAccessor(): \Symfony\Component\PropertyAccess\PropertyAccessor
    {
        return new PropertyAccessor($this->magicCall, $this->throwExceptionOnInvalidIndex, $this->cacheItemPool);
    }
}
