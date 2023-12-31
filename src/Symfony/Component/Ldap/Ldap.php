<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Ldap;

use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Exception\DriverNotFoundException;

/**
 * @author Charles Sarrazin <charles@sarraz.in>
 */
final class Ldap implements LdapInterface
{
    private readonly \Symfony\Component\Ldap\Adapter\AdapterInterface $adapter;

    private static array $adapterMap = [
        'ext_ldap' => \Symfony\Component\Ldap\Adapter\ExtLdap\Adapter::class,
    ];

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function bind($dn = null, $password = null): void
    {
        $this->adapter->getConnection()->bind($dn, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function query($dn, $query, array $options = [])
    {
        return $this->adapter->createQuery($dn, $query, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntryManager()
    {
        return $this->adapter->getEntryManager();
    }

    /**
     * {@inheritdoc}
     */
    public function escape($subject, $ignore = '', $flags = 0)
    {
        return $this->adapter->escape($subject, $ignore, $flags);
    }

    /**
     * Creates a new Ldap instance.
     *
     * @param string $adapter The adapter name
     * @param array  $config  The adapter's configuration
     *
     */
    public static function create($adapter, array $config = []): self
    {
        if (!isset(self::$adapterMap[$adapter])) {
            throw new DriverNotFoundException(sprintf('Adapter "%s" not found. You should use one of: "%s".', $adapter, implode('", "', self::$adapterMap)));
        }

        $class = self::$adapterMap[$adapter];

        return new self(new $class($config));
    }
}
