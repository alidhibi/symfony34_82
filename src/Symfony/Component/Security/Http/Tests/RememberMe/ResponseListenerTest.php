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
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;
use Symfony\Component\Security\Http\RememberMe\ResponseListener;

class ResponseListenerTest extends TestCase
{
    public function testRememberMeCookieIsSentWithResponse(): void
    {
        $cookie = new Cookie('rememberme');

        $request = $this->getRequest([
            RememberMeServicesInterface::COOKIE_ATTR_NAME => $cookie,
        ]);

        $response = $this->getResponse();
        $response->headers->expects($this->once())->method('setCookie')->with($cookie);

        $listener = new ResponseListener();
        $listener->onKernelResponse($this->getEvent($request, $response));
    }

    public function testRememberMeCookieIsNotSendWithResponseForSubRequests(): void
    {
        $cookie = new Cookie('rememberme');

        $request = $this->getRequest([
            RememberMeServicesInterface::COOKIE_ATTR_NAME => $cookie,
        ]);

        $response = $this->getResponse();
        $response->headers->expects($this->never())->method('setCookie');

        $listener = new ResponseListener();
        $listener->onKernelResponse($this->getEvent($request, $response, HttpKernelInterface::SUB_REQUEST));
    }

    public function testRememberMeCookieIsNotSendWithResponse(): void
    {
        $request = $this->getRequest();

        $response = $this->getResponse();
        $response->headers->expects($this->never())->method('setCookie');

        $listener = new ResponseListener();
        $listener->onKernelResponse($this->getEvent($request, $response));
    }

    public function testItSubscribesToTheOnKernelResponseEvent(): void
    {
        $this->assertSame([KernelEvents::RESPONSE => 'onKernelResponse'], ResponseListener::getSubscribedEvents());
    }

    private function getRequest(array $attributes = []): \Symfony\Component\HttpFoundation\Request
    {
        $request = new Request();

        foreach ($attributes as $name => $value) {
            $request->attributes->set($name, $value);
        }

        return $request;
    }

    private function getResponse(): \Symfony\Component\HttpFoundation\Response
    {
        $response = new Response();
        $response->headers = $this->getMockBuilder(\Symfony\Component\HttpFoundation\ResponseHeaderBag::class)->getMock();

        return $response;
    }

    private function getEvent($request, $response, int $type = HttpKernelInterface::MASTER_REQUEST)
    {
        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\FilterResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())->method('getRequest')->willReturn($request);
        $event->expects($this->any())->method('isMasterRequest')->willReturn(HttpKernelInterface::MASTER_REQUEST === $type);
        $event->expects($this->any())->method('getResponse')->willReturn($response);

        return $event;
    }
}
