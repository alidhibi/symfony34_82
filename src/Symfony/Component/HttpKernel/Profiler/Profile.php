<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Profiler;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Profile.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Profile
{
    private $token;

    /**
     * @var DataCollectorInterface[]
     */
    private $collectors = [];

    private $ip;

    private $method;

    private $url;

    private $time;

    private $statusCode;

    private ?\Symfony\Component\HttpKernel\Profiler\Profile $parent = null;

    /**
     * @var Profile[]
     */
    private $children = [];

    /**
     * @param string $token The token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Sets the token.
     *
     * @param string $token The token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * Gets the token.
     *
     * @return string The token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the parent token.
     */
    public function setParent(self $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Returns the parent profile.
     *
     * @return self
     */
    public function getParent(): ?\Symfony\Component\HttpKernel\Profiler\Profile
    {
        return $this->parent;
    }

    /**
     * Returns the parent token.
     *
     * @return string|null The parent token
     */
    public function getParentToken()
    {
        return $this->parent instanceof \Symfony\Component\HttpKernel\Profiler\Profile ? $this->parent->getToken() : null;
    }

    /**
     * Returns the IP.
     *
     * @return string|null The IP
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Sets the IP.
     *
     * @param string $ip
     */
    public function setIp($ip): void
    {
        $this->ip = $ip;
    }

    /**
     * Returns the request method.
     *
     * @return string|null The request method
     */
    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method): void
    {
        $this->method = $method;
    }

    /**
     * Returns the URL.
     *
     * @return string|null The URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    /**
     * Returns the time.
     *
     * @return int The time
     */
    public function getTime()
    {
        if (null === $this->time) {
            return 0;
        }

        return $this->time;
    }

    /**
     * @param int $time The time
     */
    public function setTime($time): void
    {
        $this->time = $time;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Finds children profilers.
     *
     * @return self[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets children profiler.
     *
     * @param Profile[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = [];
        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    /**
     * Adds the child token.
     */
    public function addChild(self $child): void
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /**
     * Gets a Collector by name.
     *
     * @param string $name A collector name
     *
     * @return DataCollectorInterface A DataCollectorInterface instance
     *
     * @throws \InvalidArgumentException if the collector does not exist
     */
    public function getCollector($name)
    {
        if (!isset($this->collectors[$name])) {
            throw new \InvalidArgumentException(sprintf('Collector "%s" does not exist.', $name));
        }

        return $this->collectors[$name];
    }

    /**
     * Gets the Collectors associated with this profile.
     *
     * @return DataCollectorInterface[]
     */
    public function getCollectors()
    {
        return $this->collectors;
    }

    /**
     * Sets the Collectors associated with this profile.
     *
     * @param DataCollectorInterface[] $collectors
     */
    public function setCollectors(array $collectors): void
    {
        $this->collectors = [];
        foreach ($collectors as $collector) {
            $this->addCollector($collector);
        }
    }

    /**
     * Adds a Collector.
     */
    public function addCollector(DataCollectorInterface $collector): void
    {
        $this->collectors[$collector->getName()] = $collector;
    }

    /**
     * Returns true if a Collector for the given name exists.
     *
     * @param string $name A collector name
     *
     */
    public function hasCollector($name): bool
    {
        return isset($this->collectors[$name]);
    }

    public function __sleep()
    {
        return ['token', 'parent', 'children', 'collectors', 'ip', 'method', 'url', 'time', 'statusCode'];
    }
}
