<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
final class SessionBagProxy implements SessionBagInterface
{
    private readonly \Symfony\Component\HttpFoundation\Session\SessionBagInterface $bag;

    private $data;

    private $usageIndex;

    public function __construct(SessionBagInterface $bag, array &$data, &$usageIndex)
    {
        $this->bag = $bag;
        $this->data = &$data;
        $this->usageIndex = &$usageIndex;
    }

    public function getBag(): \Symfony\Component\HttpFoundation\Session\SessionBagInterface
    {
        ++$this->usageIndex;

        return $this->bag;
    }

    public function isEmpty(): bool
    {
        if (!isset($this->data[$this->bag->getStorageKey()])) {
            return true;
        }

        ++$this->usageIndex;

        return empty($this->data[$this->bag->getStorageKey()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->bag->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array &$array): void
    {
        ++$this->usageIndex;
        $this->data[$this->bag->getStorageKey()] = &$array;

        $this->bag->initialize($array);
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageKey()
    {
        return $this->bag->getStorageKey();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->bag->clear();
    }
}
