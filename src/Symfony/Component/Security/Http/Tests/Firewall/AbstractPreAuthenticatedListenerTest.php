<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Tests\Firewall;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AbstractPreAuthenticatedListenerTest extends TestCase
{
    public function testHandleWithValidValues(): void
    {
        $userCredentials = ['TheUser', 'TheCredentials'];

        $request = new Request([], [], [], [], [], []);

        $token = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock();

        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn(null)
        ;
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;

        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();
        $authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($this->isInstanceOf(\Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken::class))
            ->willReturn($token)
        ;

        $listener = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Firewall\AbstractPreAuthenticatedListener::class, [
            $tokenStorage,
            $authenticationManager,
            'TheProviderKey',
        ]);
        $listener
            ->expects($this->once())
            ->method('getPreAuthenticatedData')
            ->willReturn($userCredentials);

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener->handle($event);
    }

    public function testHandleWhenAuthenticationFails(): void
    {
        $userCredentials = ['TheUser', 'TheCredentials'];

        $request = new Request([], [], [], [], [], []);

        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn(null)
        ;
        $tokenStorage
            ->expects($this->never())
            ->method('setToken')
        ;

        $exception = new AuthenticationException('Authentication failed.');
        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();
        $authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($this->isInstanceOf(\Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken::class))
            ->willThrowException($exception)
        ;

        $listener = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Firewall\AbstractPreAuthenticatedListener::class, [
            $tokenStorage,
            $authenticationManager,
            'TheProviderKey',
        ]);
        $listener
            ->expects($this->once())
            ->method('getPreAuthenticatedData')
            ->willReturn($userCredentials);

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener->handle($event);
    }

    public function testHandleWhenAuthenticationFailsWithDifferentToken(): void
    {
        $userCredentials = ['TheUser', 'TheCredentials'];

        $token = new UsernamePasswordToken('TheUsername', 'ThePassword', 'TheProviderKey', ['ROLE_FOO']);

        $request = new Request([], [], [], [], [], []);

        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;
        $tokenStorage
            ->expects($this->never())
            ->method('setToken')
        ;

        $exception = new AuthenticationException('Authentication failed.');
        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();
        $authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($this->isInstanceOf(\Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken::class))
            ->willThrowException($exception)
        ;

        $listener = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Firewall\AbstractPreAuthenticatedListener::class, [
            $tokenStorage,
            $authenticationManager,
            'TheProviderKey',
        ]);
        $listener
            ->expects($this->once())
            ->method('getPreAuthenticatedData')
            ->willReturn($userCredentials);

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener->handle($event);
    }

    public function testHandleWithASimilarAuthenticatedToken(): void
    {
        $userCredentials = ['TheUser', 'TheCredentials'];

        $request = new Request([], [], [], [], [], []);

        $token = new PreAuthenticatedToken('TheUser', 'TheCredentials', 'TheProviderKey', ['ROLE_FOO']);

        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();
        $authenticationManager
            ->expects($this->never())
            ->method('authenticate')
        ;

        $listener = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Firewall\AbstractPreAuthenticatedListener::class, [
            $tokenStorage,
            $authenticationManager,
            'TheProviderKey',
        ]);
        $listener
            ->expects($this->once())
            ->method('getPreAuthenticatedData')
            ->willReturn($userCredentials);

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener->handle($event);
    }

    public function testHandleWithAnInvalidSimilarToken(): void
    {
        $userCredentials = ['TheUser', 'TheCredentials'];

        $request = new Request([], [], [], [], [], []);

        $token = new PreAuthenticatedToken('AnotherUser', 'TheCredentials', 'TheProviderKey', ['ROLE_FOO']);

        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo(null))
        ;

        $exception = new AuthenticationException('Authentication failed.');
        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();
        $authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($this->isInstanceOf(\Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken::class))
            ->willThrowException($exception)
        ;

        $listener = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Firewall\AbstractPreAuthenticatedListener::class, [
            $tokenStorage,
            $authenticationManager,
            'TheProviderKey',
        ]);
        $listener
            ->expects($this->once())
            ->method('getPreAuthenticatedData')
            ->willReturn($userCredentials);

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener->handle($event);
    }
}
