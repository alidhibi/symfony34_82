<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * InMemoryUserProvider is a simple non persistent user provider.
 *
 * Useful for testing, demonstration, prototyping, and for simple needs
 * (a backend with a unique admin for instance)
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class InMemoryUserProvider implements UserProviderInterface
{
    private ?array $users = null;

    /**
     * The user array is a hash where the keys are usernames and the values are
     * an array of attributes: 'password', 'enabled', and 'roles'.
     *
     * @param array $users An array of users
     */
    public function __construct(array $users = [])
    {
        foreach ($users as $username => $attributes) {
            $password = isset($attributes['password']) ? $attributes['password'] : null;
            $enabled = isset($attributes['enabled']) ? $attributes['enabled'] : true;
            $roles = isset($attributes['roles']) ? $attributes['roles'] : [];
            $user = new User($username, $password, $roles, $enabled, true, true, true);

            $this->createUser($user);
        }
    }

    /**
     * Adds a new User to the provider.
     *
     * @throws \LogicException
     */
    public function createUser(UserInterface $user): void
    {
        if (isset($this->users[strtolower($user->getUsername())])) {
            throw new \LogicException('Another user with the same username already exists.');
        }

        $this->users[strtolower($user->getUsername())] = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username): \Symfony\Component\Security\Core\User\User
    {
        $user = $this->getUser($username);

        return new User($user->getUsername(), $user->getPassword(), $user->getRoles(), $user->isEnabled(), $user->isAccountNonExpired(), $user->isCredentialsNonExpired(), $user->isAccountNonLocked());
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): \Symfony\Component\Security\Core\User\User
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $storedUser = $this->getUser($user->getUsername());

        return new User($storedUser->getUsername(), $storedUser->getPassword(), $storedUser->getRoles(), $storedUser->isEnabled(), $storedUser->isAccountNonExpired(), $storedUser->isCredentialsNonExpired() && $storedUser->getPassword() === $user->getPassword(), $storedUser->isAccountNonLocked());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        return \Symfony\Component\Security\Core\User\User::class === $class;
    }

    /**
     * Returns the user by given username.
     *
     * @param string $username The username
     *
     * @return User
     *
     * @throws UsernameNotFoundException if user whose given username does not exist
     */
    private function getUser($username)
    {
        if (!isset($this->users[strtolower($username)])) {
            $ex = new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
            $ex->setUsername($username);

            throw $ex;
        }

        return $this->users[strtolower($username)];
    }
}
