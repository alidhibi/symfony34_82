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
use Symfony\Component\Security\Http\Firewall\RemoteUserAuthenticationListener;

class RemoteUserAuthenticationListenerTest extends TestCase
{
    public function testGetPreAuthenticatedData(): void
    {
        $serverVars = [
            'REMOTE_USER' => 'TheUser',
        ];

        $request = new Request([], [], [], [], [], $serverVars);

        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();

        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();

        $listener = new RemoteUserAuthenticationListener(
            $tokenStorage,
            $authenticationManager,
            'TheProviderKey'
        );

        $method = new \ReflectionMethod($listener, 'getPreAuthenticatedData');
        $method->setAccessible(true);

        $result = $method->invokeArgs($listener, [$request]);
        $this->assertSame($result, ['TheUser', null]);
    }

    public function testGetPreAuthenticatedDataNoUser(): void
    {
        $this->expectException(\Symfony\Component\Security\Core\Exception\BadCredentialsException::class);
        $request = new Request([], [], [], [], [], []);

        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();

        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();

        $listener = new RemoteUserAuthenticationListener(
            $tokenStorage,
            $authenticationManager,
            'TheProviderKey'
        );

        $method = new \ReflectionMethod($listener, 'getPreAuthenticatedData');
        $method->setAccessible(true);

        $method->invokeArgs($listener, [$request]);
    }

    public function testGetPreAuthenticatedDataWithDifferentKeys(): void
    {
        $userCredentials = ['TheUser', null];

        $request = new Request([], [], [], [], [], [
            'TheUserKey' => 'TheUser',
        ]);
        $tokenStorage = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)->getMock();

        $authenticationManager = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class)->getMock();

        $listener = new RemoteUserAuthenticationListener(
            $tokenStorage,
            $authenticationManager,
            'TheProviderKey',
            'TheUserKey'
        );

        $method = new \ReflectionMethod($listener, 'getPreAuthenticatedData');
        $method->setAccessible(true);

        $result = $method->invokeArgs($listener, [$request]);
        $this->assertSame($result, $userCredentials);
    }
}
