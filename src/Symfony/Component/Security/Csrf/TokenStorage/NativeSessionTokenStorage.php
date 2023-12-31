<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Csrf\TokenStorage;

use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;

/**
 * Token storage that uses PHP's native session handling.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NativeSessionTokenStorage implements ClearableTokenStorageInterface
{
    /**
     * The namespace used to store values in the session.
     */
    final const SESSION_NAMESPACE = '_csrf';

    private bool $sessionStarted = false;

    private $namespace;

    /**
     * Initializes the storage with a session namespace.
     *
     * @param string $namespace The namespace under which the token is stored in the session
     */
    public function __construct($namespace = self::SESSION_NAMESPACE)
    {
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId): string
    {
        if (!$this->sessionStarted) {
            $this->startSession();
        }

        if (!isset($_SESSION[$this->namespace][$tokenId])) {
            throw new TokenNotFoundException('The CSRF token with ID '.$tokenId.' does not exist.');
        }

        return (string) $_SESSION[$this->namespace][$tokenId];
    }

    /**
     * {@inheritdoc}
     */
    public function setToken($tokenId, $token): void
    {
        if (!$this->sessionStarted) {
            $this->startSession();
        }

        $_SESSION[$this->namespace][$tokenId] = (string) $token;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken($tokenId): bool
    {
        if (!$this->sessionStarted) {
            $this->startSession();
        }

        return isset($_SESSION[$this->namespace][$tokenId]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken($tokenId): ?string
    {
        if (!$this->sessionStarted) {
            $this->startSession();
        }

        if (!isset($_SESSION[$this->namespace][$tokenId])) {
            return null;
        }

        $token = (string) $_SESSION[$this->namespace][$tokenId];

        unset($_SESSION[$this->namespace][$tokenId]);

        if (!$_SESSION[$this->namespace]) {
            unset($_SESSION[$this->namespace]);
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        unset($_SESSION[$this->namespace]);
    }

    private function startSession(): void
    {
        if (\PHP_SESSION_NONE === session_status()) {
            session_start();
        }

        $this->sessionStarted = true;
    }
}
