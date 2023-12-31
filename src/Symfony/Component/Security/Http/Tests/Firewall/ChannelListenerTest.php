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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Firewall\ChannelListener;

class ChannelListenerTest extends TestCase
{
    public function testHandleWithNotSecuredRequestAndHttpChannel(): void
    {
        $request = $this->getMockBuilder(\Symfony\Component\HttpFoundation\Request::class)->disableOriginalConstructor()->disableOriginalClone()->getMock();
        $request
            ->expects($this->any())
            ->method('isSecure')
            ->willReturn(false)
        ;

        $accessMap = $this->getMockBuilder(\Symfony\Component\Security\Http\AccessMapInterface::class)->getMock();
        $accessMap
            ->expects($this->any())
            ->method('getPatterns')
            ->with($this->equalTo($request))
            ->willReturn([[], 'http'])
        ;

        $entryPoint = $this->getMockBuilder(\Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface::class)->getMock();
        $entryPoint
            ->expects($this->never())
            ->method('start')
        ;

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;
        $event
            ->expects($this->never())
            ->method('setResponse')
        ;

        $listener = new ChannelListener($accessMap, $entryPoint);
        $listener->handle($event);
    }

    public function testHandleWithSecuredRequestAndHttpsChannel(): void
    {
        $request = $this->getMockBuilder(\Symfony\Component\HttpFoundation\Request::class)->disableOriginalConstructor()->disableOriginalClone()->getMock();
        $request
            ->expects($this->any())
            ->method('isSecure')
            ->willReturn(true)
        ;

        $accessMap = $this->getMockBuilder(\Symfony\Component\Security\Http\AccessMapInterface::class)->getMock();
        $accessMap
            ->expects($this->any())
            ->method('getPatterns')
            ->with($this->equalTo($request))
            ->willReturn([[], 'https'])
        ;

        $entryPoint = $this->getMockBuilder(\Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface::class)->getMock();
        $entryPoint
            ->expects($this->never())
            ->method('start')
        ;

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;
        $event
            ->expects($this->never())
            ->method('setResponse')
        ;

        $listener = new ChannelListener($accessMap, $entryPoint);
        $listener->handle($event);
    }

    public function testHandleWithNotSecuredRequestAndHttpsChannel(): void
    {
        $request = $this->getMockBuilder(\Symfony\Component\HttpFoundation\Request::class)->disableOriginalConstructor()->disableOriginalClone()->getMock();
        $request
            ->expects($this->any())
            ->method('isSecure')
            ->willReturn(false)
        ;

        $response = new Response();

        $accessMap = $this->getMockBuilder(\Symfony\Component\Security\Http\AccessMapInterface::class)->getMock();
        $accessMap
            ->expects($this->any())
            ->method('getPatterns')
            ->with($this->equalTo($request))
            ->willReturn([[], 'https'])
        ;

        $entryPoint = $this->getMockBuilder(\Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface::class)->getMock();
        $entryPoint
            ->expects($this->once())
            ->method('start')
            ->with($this->equalTo($request))
            ->willReturn($response)
        ;

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;
        $event
            ->expects($this->once())
            ->method('setResponse')
            ->with($this->equalTo($response))
        ;

        $listener = new ChannelListener($accessMap, $entryPoint);
        $listener->handle($event);
    }

    public function testHandleWithSecuredRequestAndHttpChannel(): void
    {
        $request = $this->getMockBuilder(\Symfony\Component\HttpFoundation\Request::class)->disableOriginalConstructor()->disableOriginalClone()->getMock();
        $request
            ->expects($this->any())
            ->method('isSecure')
            ->willReturn(true)
        ;

        $response = new Response();

        $accessMap = $this->getMockBuilder(\Symfony\Component\Security\Http\AccessMapInterface::class)->getMock();
        $accessMap
            ->expects($this->any())
            ->method('getPatterns')
            ->with($this->equalTo($request))
            ->willReturn([[], 'http'])
        ;

        $entryPoint = $this->getMockBuilder(\Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface::class)->getMock();
        $entryPoint
            ->expects($this->once())
            ->method('start')
            ->with($this->equalTo($request))
            ->willReturn($response)
        ;

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($request)
        ;
        $event
            ->expects($this->once())
            ->method('setResponse')
            ->with($this->equalTo($response))
        ;

        $listener = new ChannelListener($accessMap, $entryPoint);
        $listener->handle($event);
    }
}
