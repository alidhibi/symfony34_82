<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Authentication;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\ProviderNotFoundException;

/**
 * AuthenticationProviderManager uses a list of AuthenticationProviderInterface
 * instances to authenticate a Token.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AuthenticationProviderManager implements AuthenticationManagerInterface
{
    private $providers;

    private readonly bool $eraseCredentials;

    private ?\Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher = null;

    /**
     * @param iterable|AuthenticationProviderInterface[] $providers        An iterable with AuthenticationProviderInterface instances as values
     * @param bool                                       $eraseCredentials Whether to erase credentials after authentication or not
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($providers, $eraseCredentials = true)
    {
        if (!$providers) {
            throw new \InvalidArgumentException('You must at least add one authentication provider.');
        }

        $this->providers = $providers;
        $this->eraseCredentials = (bool) $eraseCredentials;
    }

    public function setEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        $lastException = null;
        $result = null;

        foreach ($this->providers as $provider) {
            if (!$provider instanceof AuthenticationProviderInterface) {
                throw new \InvalidArgumentException(sprintf('Provider "%s" must implement the AuthenticationProviderInterface.', \get_class($provider)));
            }

            if (!$provider->supports($token)) {
                continue;
            }

            try {
                $result = $provider->authenticate($token);

                if (null !== $result) {
                    break;
                }
            } catch (AccountStatusException $e) {
                $lastException = $e;

                break;
            } catch (AuthenticationException $e) {
                $lastException = $e;
            }
        }

        if (null !== $result) {
            if ($this->eraseCredentials) {
                $result->eraseCredentials();
            }

            if ($this->eventDispatcher instanceof \Symfony\Component\EventDispatcher\EventDispatcherInterface) {
                $this->eventDispatcher->dispatch(AuthenticationEvents::AUTHENTICATION_SUCCESS, new AuthenticationEvent($result));
            }

            return $result;
        }

        if (!$lastException instanceof \Symfony\Component\Security\Core\Exception\AuthenticationException) {
            $lastException = new ProviderNotFoundException(sprintf('No Authentication Provider found for token of class "%s".', \get_class($token)));
        }

        if ($this->eventDispatcher instanceof \Symfony\Component\EventDispatcher\EventDispatcherInterface) {
            $this->eventDispatcher->dispatch(AuthenticationEvents::AUTHENTICATION_FAILURE, new AuthenticationFailureEvent($token, $lastException));
        }

        $lastException->setToken($token);

        throw $lastException;
    }
}
