<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\SimpleAuthenticationHandler;

class SimpleAuthenticationHandlerTest extends TestCase
{
    private $successHandler;

    private $failureHandler;

    private $request;

    private $token;

    private \Symfony\Component\Security\Core\Exception\AuthenticationException $authenticationException;

    private \Symfony\Component\HttpFoundation\Response $response;

    protected function setUp()
    {
        $this->successHandler = $this->getMockBuilder(\Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface::class)->getMock();
        $this->failureHandler = $this->getMockBuilder(\Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface::class)->getMock();

        $this->request = $this->getMockBuilder(\Symfony\Component\HttpFoundation\Request::class)->getMock();
        $this->token = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock();
        // No methods are invoked on the exception; we just assert on its class
        $this->authenticationException = new AuthenticationException();

        $this->response = new Response();
    }

    public function testOnAuthenticationSuccessFallsBackToDefaultHandlerIfSimpleIsNotASuccessHandler(): void
    {
        $authenticator = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface::class)->getMock();

        $this->successHandler->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->willReturn($this->response);

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationSuccess($this->request, $this->token);

        $this->assertSame($this->response, $result);
    }

    public function testOnAuthenticationSuccessCallsSimpleAuthenticator(): void
    {
        $this->successHandler->expects($this->never())
            ->method('onAuthenticationSuccess');

        $authenticator = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Tests\TestSuccessHandlerInterface::class);
        $authenticator->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->willReturn($this->response);

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationSuccess($this->request, $this->token);

        $this->assertSame($this->response, $result);
    }

    public function testOnAuthenticationSuccessThrowsAnExceptionIfNonResponseIsReturned(): void
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('onAuthenticationSuccess()" method must return null to use the default success handler, or a Response object');
        $this->successHandler->expects($this->never())
            ->method('onAuthenticationSuccess');

        $authenticator = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Tests\TestSuccessHandlerInterface::class);
        $authenticator->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->willReturn(new \stdClass());

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $handler->onAuthenticationSuccess($this->request, $this->token);
    }

    public function testOnAuthenticationSuccessFallsBackToDefaultHandlerIfNullIsReturned(): void
    {
        $this->successHandler->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->willReturn($this->response);

        $authenticator = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Tests\TestSuccessHandlerInterface::class);
        $authenticator->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->willReturn(null);

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationSuccess($this->request, $this->token);

        $this->assertSame($this->response, $result);
    }

    public function testOnAuthenticationFailureFallsBackToDefaultHandlerIfSimpleIsNotAFailureHandler(): void
    {
        $authenticator = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface::class)->getMock();

        $this->failureHandler->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->willReturn($this->response);

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationFailure($this->request, $this->authenticationException);

        $this->assertSame($this->response, $result);
    }

    public function testOnAuthenticationFailureCallsSimpleAuthenticator(): void
    {
        $this->failureHandler->expects($this->never())
            ->method('onAuthenticationFailure');

        $authenticator = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Tests\TestFailureHandlerInterface::class);
        $authenticator->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->willReturn($this->response);

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationFailure($this->request, $this->authenticationException);

        $this->assertSame($this->response, $result);
    }

    public function testOnAuthenticationFailureThrowsAnExceptionIfNonResponseIsReturned(): void
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('onAuthenticationFailure()" method must return null to use the default failure handler, or a Response object');
        $this->failureHandler->expects($this->never())
            ->method('onAuthenticationFailure');

        $authenticator = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Tests\TestFailureHandlerInterface::class);
        $authenticator->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->willReturn(new \stdClass());

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $handler->onAuthenticationFailure($this->request, $this->authenticationException);
    }

    public function testOnAuthenticationFailureFallsBackToDefaultHandlerIfNullIsReturned(): void
    {
        $this->failureHandler->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->willReturn($this->response);

        $authenticator = $this->getMockForAbstractClass(\Symfony\Component\Security\Http\Tests\TestFailureHandlerInterface::class);
        $authenticator->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->willReturn(null);

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationFailure($this->request, $this->authenticationException);

        $this->assertSame($this->response, $result);
    }
}

interface TestSuccessHandlerInterface extends AuthenticationSuccessHandlerInterface, SimpleAuthenticatorInterface
{
}

interface TestFailureHandlerInterface extends AuthenticationFailureHandlerInterface, SimpleAuthenticatorInterface
{
}
