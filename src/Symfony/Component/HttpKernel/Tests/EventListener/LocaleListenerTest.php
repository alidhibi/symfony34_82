<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LocaleListenerTest extends TestCase
{
    private $requestStack;

    protected function setUp()
    {
        $this->requestStack = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestStack::class)->disableOriginalConstructor()->getMock();
    }

    public function testDefaultLocaleWithoutSession(): void
    {
        $listener = new LocaleListener($this->requestStack, 'fr');
        $event = $this->getEvent($request = Request::create('/'));

        $listener->onKernelRequest($event);
        $this->assertEquals('fr', $request->getLocale());
    }

    public function testLocaleFromRequestAttribute(): void
    {
        $request = Request::create('/');
        $request->cookies->set(session_name(), 'value');

        $request->attributes->set('_locale', 'es');
        $listener = new LocaleListener($this->requestStack, 'fr');
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        $this->assertEquals('es', $request->getLocale());
    }

    public function testLocaleSetForRoutingContext(): void
    {
        // the request context is updated
        $context = $this->getMockBuilder(\Symfony\Component\Routing\RequestContext::class)->getMock();
        $context->expects($this->once())->method('setParameter')->with('_locale', 'es');

        $router = $this->getMockBuilder(\Symfony\Component\Routing\Router::class)->setMethods(['getContext'])->disableOriginalConstructor()->getMock();
        $router->expects($this->once())->method('getContext')->willReturn($context);

        $request = Request::create('/');

        $request->attributes->set('_locale', 'es');
        $listener = new LocaleListener($this->requestStack, 'fr', $router);
        $listener->onKernelRequest($this->getEvent($request));
    }

    public function testRouterResetWithParentRequestOnKernelFinishRequest(): void
    {
        // the request context is updated
        $context = $this->getMockBuilder(\Symfony\Component\Routing\RequestContext::class)->getMock();
        $context->expects($this->once())->method('setParameter')->with('_locale', 'es');

        $router = $this->getMockBuilder(\Symfony\Component\Routing\Router::class)->setMethods(['getContext'])->disableOriginalConstructor()->getMock();
        $router->expects($this->once())->method('getContext')->willReturn($context);

        $parentRequest = Request::create('/');
        $parentRequest->setLocale('es');

        $this->requestStack->expects($this->once())->method('getParentRequest')->willReturn($parentRequest);

        $event = $this->getMockBuilder(\Symfony\Component\HttpKernel\Event\FinishRequestEvent::class)->disableOriginalConstructor()->getMock();

        $listener = new LocaleListener($this->requestStack, 'fr', $router);
        $listener->onKernelFinishRequest($event);
    }

    public function testRequestLocaleIsNotOverridden(): void
    {
        $request = Request::create('/');
        $request->setLocale('de');

        $listener = new LocaleListener($this->requestStack, 'fr');
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        $this->assertEquals('de', $request->getLocale());
    }

    private function getEvent(Request $request): \Symfony\Component\HttpKernel\Event\GetResponseEvent
    {
        return new GetResponseEvent($this->getMockBuilder(\Symfony\Component\HttpKernel\HttpKernelInterface::class)->getMock(), $request, HttpKernelInterface::MASTER_REQUEST);
    }
}