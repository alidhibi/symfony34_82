<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Firewall;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

/**
 * AbstractPreAuthenticatedListener is the base class for all listener that
 * authenticates users based on a pre-authenticated request (like a certificate
 * for instance).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AbstractPreAuthenticatedListener implements ListenerInterface
{
    protected ?\Psr\Log\LoggerInterface $logger;

    private readonly \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage;

    private readonly \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface $authenticationManager;

    private $providerKey;

    private ?\Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher = null;

    private ?\Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface $sessionStrategy = null;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, $providerKey, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handles pre-authentication.
     */
    final public function handle(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        try {
            list($user, $credentials) = $this->getPreAuthenticatedData($request);
        } catch (BadCredentialsException $badCredentialsException) {
            $this->clearToken($badCredentialsException);

            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Checking current security token.', ['token' => (string) $this->tokenStorage->getToken()]);
        }

        if (null !== ($token = $this->tokenStorage->getToken()) && ($token instanceof PreAuthenticatedToken && $this->providerKey == $token->getProviderKey() && $token->isAuthenticated() && $token->getUsername() === $user)) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Trying to pre-authenticate user.', ['username' => (string) $user]);
        }

        try {
            $token = $this->authenticationManager->authenticate(new PreAuthenticatedToken($user, $credentials, $this->providerKey));

            if (null !== $this->logger) {
                $this->logger->info('Pre-authentication successful.', ['token' => (string) $token]);
            }

            $this->migrateSession($request, $token);

            $this->tokenStorage->setToken($token);

            if ($this->dispatcher instanceof \Symfony\Component\EventDispatcher\EventDispatcherInterface) {
                $loginEvent = new InteractiveLoginEvent($request, $token);
                $this->dispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
            }
        } catch (AuthenticationException $authenticationException) {
            $this->clearToken($authenticationException);
        }
    }

    /**
     * Call this method if your authentication token is stored to a session.
     *
     * @final
     */
    public function setSessionAuthenticationStrategy(SessionAuthenticationStrategyInterface $sessionStrategy): void
    {
        $this->sessionStrategy = $sessionStrategy;
    }

    /**
     * Clears a PreAuthenticatedToken for this provider (if present).
     */
    private function clearToken(AuthenticationException $exception): void
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof PreAuthenticatedToken && $this->providerKey === $token->getProviderKey()) {
            $this->tokenStorage->setToken(null);

            if (null !== $this->logger) {
                $this->logger->info('Cleared security token due to an exception.', ['exception' => $exception]);
            }
        }
    }

    /**
     * Gets the user and credentials from the Request.
     *
     * @return array An array composed of the user and the credentials
     */
    abstract protected function getPreAuthenticatedData(Request $request);

    private function migrateSession(Request $request, TokenInterface $token): void
    {
        if (!$this->sessionStrategy || !$request->hasSession() || !$request->hasPreviousSession()) {
            return;
        }

        $this->sessionStrategy->onAuthentication($request, $token);
    }
}
