<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class SimpleAuthenticationProvider implements AuthenticationProviderInterface
{
    private readonly \Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface $simpleAuthenticator;

    private readonly \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider;

    private $providerKey;

    private readonly \Symfony\Component\Security\Core\User\UserCheckerInterface $userChecker;

    public function __construct(SimpleAuthenticatorInterface $simpleAuthenticator, UserProviderInterface $userProvider, $providerKey, UserCheckerInterface $userChecker = null)
    {
        $this->simpleAuthenticator = $simpleAuthenticator;
        $this->userProvider = $userProvider;
        $this->providerKey = $providerKey;
        $this->userChecker = $userChecker ?: new UserChecker();
    }

    public function authenticate(TokenInterface $token)
    {
        $authToken = $this->simpleAuthenticator->authenticateToken($token, $this->userProvider, $this->providerKey);

        if (!$authToken instanceof TokenInterface) {
            throw new AuthenticationException('Simple authenticator failed to return an authenticated token.');
        }

        $user = $authToken->getUser();

        if (!$user instanceof UserInterface) {
            return $authToken;
        }

        $this->userChecker->checkPreAuth($user);
        $this->userChecker->checkPostAuth($user);

        return $authToken;
    }

    public function supports(TokenInterface $token)
    {
        return $this->simpleAuthenticator->supportsToken($token, $this->providerKey);
    }
}
