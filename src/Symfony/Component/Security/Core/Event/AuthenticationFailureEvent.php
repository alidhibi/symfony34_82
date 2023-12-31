<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Event;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * This event is dispatched on authentication failure.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AuthenticationFailureEvent extends AuthenticationEvent
{
    private readonly \Symfony\Component\Security\Core\Exception\AuthenticationException $authenticationException;

    public function __construct(TokenInterface $token, AuthenticationException $ex)
    {
        parent::__construct($token);

        $this->authenticationException = $ex;
    }

    public function getAuthenticationException(): \Symfony\Component\Security\Core\Exception\AuthenticationException
    {
        return $this->authenticationException;
    }
}
