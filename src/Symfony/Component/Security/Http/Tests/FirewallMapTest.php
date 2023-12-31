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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\FirewallMap;

class FirewallMapTest extends TestCase
{
    public function testGetListeners(): void
    {
        $map = new FirewallMap();

        $request = new Request();

        $notMatchingMatcher = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestMatcher::class)->getMock();
        $notMatchingMatcher
            ->expects($this->once())
            ->method('matches')
            ->with($this->equalTo($request))
            ->willReturn(false)
        ;

        $map->add($notMatchingMatcher, [$this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ListenerInterface::class)->getMock()]);

        $matchingMatcher = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestMatcher::class)->getMock();
        $matchingMatcher
            ->expects($this->once())
            ->method('matches')
            ->with($this->equalTo($request))
            ->willReturn(true)
        ;
        $theListener = $this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ListenerInterface::class)->getMock();
        $theException = $this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ExceptionListener::class)->disableOriginalConstructor()->getMock();

        $map->add($matchingMatcher, [$theListener], $theException);

        $tooLateMatcher = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestMatcher::class)->getMock();
        $tooLateMatcher
            ->expects($this->never())
            ->method('matches')
        ;

        $map->add($tooLateMatcher, [$this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ListenerInterface::class)->getMock()]);

        list($listeners, $exception) = $map->getListeners($request);

        $this->assertEquals([$theListener], $listeners);
        $this->assertEquals($theException, $exception);
    }

    public function testGetListenersWithAnEntryHavingNoRequestMatcher(): void
    {
        $map = new FirewallMap();

        $request = new Request();

        $notMatchingMatcher = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestMatcher::class)->getMock();
        $notMatchingMatcher
            ->expects($this->once())
            ->method('matches')
            ->with($this->equalTo($request))
            ->willReturn(false)
        ;

        $map->add($notMatchingMatcher, [$this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ListenerInterface::class)->getMock()]);

        $theListener = $this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ListenerInterface::class)->getMock();
        $theException = $this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ExceptionListener::class)->disableOriginalConstructor()->getMock();

        $map->add(null, [$theListener], $theException);

        $tooLateMatcher = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestMatcher::class)->getMock();
        $tooLateMatcher
            ->expects($this->never())
            ->method('matches')
        ;

        $map->add($tooLateMatcher, [$this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ListenerInterface::class)->getMock()]);

        list($listeners, $exception) = $map->getListeners($request);

        $this->assertEquals([$theListener], $listeners);
        $this->assertEquals($theException, $exception);
    }

    public function testGetListenersWithNoMatchingEntry(): void
    {
        $map = new FirewallMap();

        $request = new Request();

        $notMatchingMatcher = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestMatcher::class)->getMock();
        $notMatchingMatcher
            ->expects($this->once())
            ->method('matches')
            ->with($this->equalTo($request))
            ->willReturn(false)
        ;

        $map->add($notMatchingMatcher, [$this->getMockBuilder(\Symfony\Component\Security\Http\Firewall\ListenerInterface::class)->getMock()]);

        list($listeners, $exception) = $map->getListeners($request);

        $this->assertEquals([], $listeners);
        $this->assertNull($exception);
    }
}
