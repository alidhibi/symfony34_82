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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * AnonymousAuthenticationListener automatically adds a Token if none is
 * already present.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AnonymousAuthenticationListener implements ListenerInterface
{
    private readonly \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage;

    private $secret;

    private ?\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface $authenticationManager = null;

    private ?\Psr\Log\LoggerInterface $logger = null;

    public function __construct(TokenStorageInterface $tokenStorage, $secret, LoggerInterface $logger = null, AuthenticationManagerInterface $authenticationManager = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->secret = $secret;
        $this->authenticationManager = $authenticationManager;
        $this->logger = $logger;
    }

    /**
     * Handles anonymous authentication.
     */
    public function handle(GetResponseEvent $event): void
    {
        if (null !== $this->tokenStorage->getToken()) {
            return;
        }

        try {
            $token = new AnonymousToken($this->secret, 'anon.', []);
            if ($this->authenticationManager instanceof \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface) {
                $token = $this->authenticationManager->authenticate($token);
            }

            $this->tokenStorage->setToken($token);

            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->info('Populated the TokenStorage with an anonymous Token.');
            }
        } catch (AuthenticationException $authenticationException) {
            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->info('Anonymous authentication failed.', ['exception' => $authenticationException]);
            }
        }
    }
}
