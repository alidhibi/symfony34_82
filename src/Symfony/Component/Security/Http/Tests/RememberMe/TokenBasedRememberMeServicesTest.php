<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Tests\RememberMe;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;
use Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices;

class TokenBasedRememberMeServicesTest extends TestCase
{
    public function testAutoLoginReturnsNullWhenNoCookie(): void
    {
        $service = $this->getService(null, ['name' => 'foo']);

        $this->assertNull($service->autoLogin(new Request()));
    }

    public function testAutoLoginThrowsExceptionOnInvalidCookie(): void
    {
        $service = $this->getService(null, ['name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => false, 'remember_me_parameter' => 'foo']);
        $request = new Request();
        $request->request->set('foo', 'true');

        $request->cookies->set('foo', 'foo');

        $this->assertNull($service->autoLogin($request));
        $this->assertTrue($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)->isCleared());
    }

    public function testAutoLoginThrowsExceptionOnNonExistentUser(): void
    {
        $userProvider = $this->getProvider();
        $service = $this->getService($userProvider, ['name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => true, 'lifetime' => 3600]);
        $request = new Request();
        $request->cookies->set('foo', $this->getCookie('fooclass', 'foouser', time() + 3600, 'foopass'));

        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willThrowException(new UsernameNotFoundException('user not found'))
        ;

        $this->assertNull($service->autoLogin($request));
        $this->assertTrue($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)->isCleared());
    }

    public function testAutoLoginDoesNotAcceptCookieWithInvalidHash(): void
    {
        $userProvider = $this->getProvider();
        $service = $this->getService($userProvider, ['name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => true, 'lifetime' => 3600]);
        $request = new Request();
        $request->cookies->set('foo', base64_encode('class:'.base64_encode('foouser').':123456789:fooHash'));

        $user = $this->getMockBuilder(\Symfony\Component\Security\Core\User\UserInterface::class)->getMock();
        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foopass')
        ;

        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($this->equalTo('foouser'))
            ->willReturn($user)
        ;

        $this->assertNull($service->autoLogin($request));
        $this->assertTrue($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)->isCleared());
    }

    public function testAutoLoginDoesNotAcceptAnExpiredCookie(): void
    {
        $userProvider = $this->getProvider();
        $service = $this->getService($userProvider, ['name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => true, 'lifetime' => 3600]);
        $request = new Request();
        $request->cookies->set('foo', $this->getCookie('fooclass', 'foouser', time() - 1, 'foopass'));

        $user = $this->getMockBuilder(\Symfony\Component\Security\Core\User\UserInterface::class)->getMock();
        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foopass')
        ;

        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($this->equalTo('foouser'))
            ->willReturn($user)
        ;

        $this->assertNull($service->autoLogin($request));
        $this->assertTrue($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)->isCleared());
    }

    /**
     * @dataProvider provideUsernamesForAutoLogin
     *
     */
    public function testAutoLogin(string $username): void
    {
        $user = $this->getMockBuilder(\Symfony\Component\Security\Core\User\UserInterface::class)->getMock();
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_FOO'])
        ;
        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foopass')
        ;

        $userProvider = $this->getProvider();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($this->equalTo($username))
            ->willReturn($user)
        ;

        $service = $this->getService($userProvider, ['name' => 'foo', 'always_remember_me' => true, 'lifetime' => 3600]);
        $request = new Request();
        $request->cookies->set('foo', $this->getCookie('fooclass', $username, time() + 3600, 'foopass'));

        $returnedToken = $service->autoLogin($request);

        $this->assertInstanceOf(\Symfony\Component\Security\Core\Authentication\Token\RememberMeToken::class, $returnedToken);
        $this->assertSame($user, $returnedToken->getUser());
        $this->assertEquals('foosecret', $returnedToken->getSecret());
    }

    public function provideUsernamesForAutoLogin(): array
    {
        return [
            ['foouser', 'Simple username'],
            ['foo'.TokenBasedRememberMeServices::COOKIE_DELIMITER.'user', 'Username might contain the delimiter'],
        ];
    }

    public function testLogout(): void
    {
        $service = $this->getService(null, ['name' => 'foo', 'path' => null, 'domain' => null, 'secure' => true, 'httponly' => false]);
        $request = new Request();
        $response = new Response();
        $token = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock();

        $service->logout($request, $response, $token);

        $cookie = $request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME);
        $this->assertTrue($cookie->isCleared());
        $this->assertEquals('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
    }

    public function testLoginFail(): void
    {
        $service = $this->getService(null, ['name' => 'foo', 'path' => '/foo', 'domain' => 'foodomain.foo']);
        $request = new Request();

        $service->loginFail($request);

        $cookie = $request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME);
        $this->assertTrue($cookie->isCleared());
        $this->assertEquals('/foo', $cookie->getPath());
        $this->assertEquals('foodomain.foo', $cookie->getDomain());
    }

    public function testLoginSuccessIgnoresTokensWhichDoNotContainAnUserInterfaceImplementation(): void
    {
        $service = $this->getService(null, ['name' => 'foo', 'always_remember_me' => true, 'path' => null, 'domain' => null]);
        $request = new Request();
        $response = new Response();
        $token = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock();
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn('foo')
        ;

        $cookies = $response->headers->getCookies();
        $this->assertCount(0, $cookies);

        $service->loginSuccess($request, $response, $token);

        $cookies = $response->headers->getCookies();
        $this->assertCount(0, $cookies);
    }

    public function testLoginSuccess(): void
    {
        $service = $this->getService(null, ['name' => 'foo', 'domain' => 'myfoodomain.foo', 'path' => '/foo/path', 'secure' => true, 'httponly' => true, 'samesite' => Cookie::SAMESITE_STRICT, 'lifetime' => 3600, 'always_remember_me' => true]);
        $request = new Request();
        $response = new Response();

        $token = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock();
        $user = $this->getMockBuilder(\Symfony\Component\Security\Core\User\UserInterface::class)->getMock();
        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foopass')
        ;
        $user
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('foouser')
        ;
        $token
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user)
        ;

        $cookies = $response->headers->getCookies();
        $this->assertCount(0, $cookies);

        $service->loginSuccess($request, $response, $token);

        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        $cookie = $cookies['myfoodomain.foo']['/foo/path']['foo'];
        $this->assertFalse($cookie->isCleared());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->getExpiresTime() > time() + 3590 && $cookie->getExpiresTime() < time() + 3610);
        $this->assertEquals('myfoodomain.foo', $cookie->getDomain());
        $this->assertEquals('/foo/path', $cookie->getPath());
        $this->assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
    }

    protected function getCookie($class, $username, $expires, $password)
    {
        $service = $this->getService();
        $r = new \ReflectionMethod($service, 'generateCookieValue');
        $r->setAccessible(true);

        return $r->invoke($service, $class, $username, $expires, $password);
    }

    protected function encodeCookie(array $parts)
    {
        $service = $this->getService();
        $r = new \ReflectionMethod($service, 'encodeCookie');
        $r->setAccessible(true);

        return $r->invoke($service, $parts);
    }

    protected function getService($userProvider = null, $options = [], $logger = null): \Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices
    {
        if (null === $userProvider) {
            $userProvider = $this->getProvider();
        }

        return new TokenBasedRememberMeServices([$userProvider], 'foosecret', 'fookey', $options, $logger);
    }

    protected function getProvider()
    {
        $provider = $this->getMockBuilder(\Symfony\Component\Security\Core\User\UserProviderInterface::class)->getMock();
        $provider
            ->expects($this->any())
            ->method('supportsClass')
            ->willReturn(true)
        ;

        return $provider;
    }
}
