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
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\TranslatorListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TranslatorListenerTest extends TestCase
{
    private \Symfony\Component\HttpKernel\EventListener\TranslatorListener $listener;

    private $translator;

    private $requestStack;

    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(\Symfony\Component\Translation\TranslatorInterface::class)->getMock();
        $this->requestStack = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestStack::class)->getMock();
        $this->listener = new TranslatorListener($this->translator, $this->requestStack);
    }

    public function testLocaleIsSetInOnKernelRequest(): void
    {
        $this->translator
            ->expects($this->once())
            ->method('setLocale')
            ->with($this->equalTo('fr'));

        $event = new GetResponseEvent($this->createHttpKernel(), $this->createRequest('fr'), HttpKernelInterface::MASTER_REQUEST);
        $this->listener->onKernelRequest($event);
    }

    public function testDefaultLocaleIsUsedOnExceptionsInOnKernelRequest(): void
    {
        $this->translator
            ->expects($this->exactly(2))
            ->method('setLocale')
            ->withConsecutive(
                ['fr'],
                ['en']
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \InvalidArgumentException())
            );

        $event = new GetResponseEvent($this->createHttpKernel(), $this->createRequest('fr'), HttpKernelInterface::MASTER_REQUEST);
        $this->listener->onKernelRequest($event);
    }

    public function testLocaleIsSetInOnKernelFinishRequestWhenParentRequestExists(): void
    {
        $this->translator
            ->expects($this->once())
            ->method('setLocale')
            ->with($this->equalTo('fr'));

        $this->setMasterRequest($this->createRequest('fr'));
        $event = new FinishRequestEvent($this->createHttpKernel(), $this->createRequest('de'), HttpKernelInterface::SUB_REQUEST);
        $this->listener->onKernelFinishRequest($event);
    }

    public function testLocaleIsNotSetInOnKernelFinishRequestWhenParentRequestDoesNotExist(): void
    {
        $this->translator
            ->expects($this->never())
            ->method('setLocale');

        $event = new FinishRequestEvent($this->createHttpKernel(), $this->createRequest('de'), HttpKernelInterface::SUB_REQUEST);
        $this->listener->onKernelFinishRequest($event);
    }

    public function testDefaultLocaleIsUsedOnExceptionsInOnKernelFinishRequest(): void
    {
        $this->translator
            ->expects($this->exactly(2))
            ->method('setLocale')
            ->withConsecutive(
                ['fr'],
                ['en']
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \InvalidArgumentException())
            );

        $this->setMasterRequest($this->createRequest('fr'));
        $event = new FinishRequestEvent($this->createHttpKernel(), $this->createRequest('de'), HttpKernelInterface::SUB_REQUEST);
        $this->listener->onKernelFinishRequest($event);
    }

    private function createHttpKernel()
    {
        return $this->getMockBuilder(\Symfony\Component\HttpKernel\HttpKernelInterface::class)->getMock();
    }

    private function createRequest(string $locale): \Symfony\Component\HttpFoundation\Request
    {
        $request = new Request();
        $request->setLocale($locale);

        return $request;
    }

    private function setMasterRequest($request): void
    {
        $this->requestStack
            ->expects($this->any())
            ->method('getParentRequest')
            ->willReturn($request);
    }
}
