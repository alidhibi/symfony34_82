<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Exception;

/**
 * UsernameNotFoundException is thrown if a User cannot be found by its username.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Alexander <iam.asm89@gmail.com>
 */
class UsernameNotFoundException extends AuthenticationException
{
    private $username;

    /**
     * {@inheritdoc}
     */
    public function getMessageKey(): string
    {
        return 'Username could not be found.';
    }

    /**
     * Get the username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the username.
     *
     * @param string $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $serialized = [$this->username, parent::serialize(true)];

        return $this->doSerialize($serialized, \func_num_args() !== 0 ? func_get_arg(0) : null);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str): void
    {
        list($this->username, $parentData) = \is_array($str) ? $str : unserialize($str);

        parent::unserialize($parentData);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageData(): array
    {
        return ['{{ username }}' => $this->username];
    }
}
