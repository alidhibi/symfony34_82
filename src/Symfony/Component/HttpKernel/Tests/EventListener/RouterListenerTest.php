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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\EventListener\ValidateRequestListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\RequestContext;

class RouterListenerTest extends TestCase
{
    private $requestStack;

    protected function setUp()
    {
        $this->requestStack = $this->getMockBuilder(\Symfony\Component\HttpFoundation\RequestStack::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @dataProvider getPortData
     */
    public function testPort(int $defaultHttpPort, int $defaultHttpsPort, string $uri, int $expectedHttpPort, int $expectedHttpsPort): void
    {
        $urlMatcher = $this->getMockBuilder(\Symfony\Component\Routing\Matcher\UrlMatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context = new RequestContext();
        $context->setHttpPort($defaultHttpPort);
        $context->setHttpsPort($defaultHttpsPort);
        $urlMatcher->expects($this->any())
            ->method('getContext')
            ->willReturn($context);

        $listener = new RouterListener($urlMatcher, $this->requestStack);
        $event = $this->createGetResponseEventForUri($uri);
        $listener->onKernelRequest($event);

        $this->assertEquals($expectedHttpPort, $context->getHttpPort());
        $this->assertEquals($expectedHttpsPort, $context->getHttpsPort());
        $this->assertEquals(0 === strpos($uri, 'https') ? 'https' : 'http', $context->getScheme());
    }

    public function getPortData(): array
    {
        return [
            [80, 443, 'http://localhost/', 80, 443],
            [80, 443, 'http://localhost:90/', 90, 443],
            [80, 443, 'https://localhost/', 80, 443],
            [80, 443, 'https://localhost:90/', 80, 90],
        ];
    }

    
    private function createGetResponseEventForUri(string $uri): \Symfony\Component\HttpKernel\Event\GetResponseEvent
    {
        $kernel = $this->getMockBuilder(\Symfony\Component\HttpKernel\HttpKernelInterface::class)->getMock();
        $request = Request::create($uri);
        $request->attributes->set('_controller', null); // Prevents going in to routing process

        return new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
    }

    public function testInvalidMatcher(): void
    {
        $this->expectException('InvalidArgumentException');
        new RouterListener(new \stdClass(), $this->requestStack);
    }

    public function testRequestMatcher(): void
    {
        $kernel = $this->getMockBuilder(\Symfony\Component\HttpKernel\HttpKernelInterface::class)->getMock();
        $request = Request::create('http://localhost/');
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $requestMatcher = $this->getMockBuilder(\Symfony\Component\Routing\Matcher\RequestMatcherInterface::class)->getMock();
        $requestMatcher->expects($this->once())
                       ->method('matchRequest')
                       ->with($this->isInstanceOf(\Symfony\Component\HttpFoundation\Request::class))
                       ->willReturn([]);

        $listener = new RouterListener($requestMatcher, $this->requestStack, new RequestContext());
        $listener->onKernelRequest($event);
    }

    public function testSubRequestWithDifferentMethod(): void
    {
        $kernel = $this->getMockBuilder(\Symfony\Component\HttpKernel\HttpKernelInterface::class)->getMock();
        $request = Request::create('http://localhost/', 'post');
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $requestMatcher = $this->getMockBuilder(\Symfony\Component\Routing\Matcher\RequestMatcherInterface::class)->getMock();
        $requestMatcher->expects($this->any())
                       ->method('matchRequest')
                       ->with($this->isInstanceOf(\Symfony\Component\HttpFoundation\Request::class))
                       ->willReturn([]);

        $context = new RequestContext();

        $listener = new RouterListener($requestMatcher, $this->requestStack, new RequestContext());
        $listener->onKernelRequest($event);

        // sub-request with another HTTP method
        $kernel = $this->getMockBuilder(\Symfony\Component\HttpKernel\HttpKernelInterface::class)->getMock();
        $request = Request::create('http://localhost/', 'get');
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        $this->assertEquals('GET', $context->getMethod());
    }

    /**
     * @dataProvider getLoggingParameterData
     */
    public function testLoggingParameter(array $parameter, string $log, array $parameters): void
    {
        $requestMatcher = $this->getMockBuilder(\Symfony\Component\Routing\Matcher\RequestMatcherInterface::class)->getMock();
        $requestMatcher->expects($this->once())
            ->method('matchRequest')
            ->willReturn($parameter);

        $logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->equalTo($log), $this->equalTo($parameters));

        $kernel = $this->getMockBuilder(\Symfony\Component\HttpKernel\HttpKernelInterface::class)->getMock();
        $request = Request::create('http://localhost/');

        $listener = new RouterListener($requestMatcher, $this->requestStack, new RequestContext(), $logger);
        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));
    }

    public function getLoggingParameterData(): array
    {
        return [
            [['_route' => 'foo'], 'Matched route "{route}".', ['route' => 'foo', 'route_parameters' => ['_route' => 'foo'], 'request_uri' => 'http://localhost/', 'method' => 'GET']],
            [[], 'Matched route "{route}".', ['route' => 'n/a', 'route_parameters' => [], 'request_uri' => 'http://localhost/', 'method' => 'GET']],
        ];
    }

    public function testWithBadRequest(): void
    {
        $requestStack = new RequestStack();

        $requestMatcher = $this->getMockBuilder(\Symfony\Component\Routing\Matcher\RequestMatcherInterface::class)->getMock();
        $requestMatcher->expects($this->never())->method('matchRequest');

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new ValidateRequestListener());
        $dispatcher->addSubscriber(new RouterListener($requestMatcher, $requestStack, new RequestContext()));
        $dispatcher->addSubscriber(new ExceptionListener(static fn(): \Symfony\Component\HttpFoundation\Response => new Response('Exception handled', 400)));

        $kernel = new HttpKernel($dispatcher, new ControllerResolver(), $requestStack, new ArgumentResolver());

        $request = Request::create('http://localhost/');
        $request->headers->set('host', '###');
        $response = $kernel->handle($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testNoRoutingConfigurationResponse(): void
    {
        $requestStack = new RequestStack();

        $requestMatcher = $this->getMockBuilder(\Symfony\Component\Routing\Matcher\RequestMatcherInterface::class)->getMock();
        $requestMatcher
            ->expects($this->once())
            ->method('matchRequest')
            ->willThrowException(new NoConfigurationException())
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener($requestMatcher, $requestStack, new RequestContext()));

        $kernel = new HttpKernel($dispatcher, new ControllerResolver(), $requestStack, new ArgumentResolver());

        $request = Request::create('http://localhost/');
        $response = $kernel->handle($request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Welcome', $response->getContent());
    }

    public function testRequestWithBadHost(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $kernel = $this->getMockBuilder(\Symfony\Component\HttpKernel\HttpKernelInterface::class)->getMock();
        $request = Request::create('http://bad host %22/');
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $requestMatcher = $this->getMockBuilder(\Symfony\Component\Routing\Matcher\RequestMatcherInterface::class)->getMock();

        $listener = new RouterListener($requestMatcher, $this->requestStack, new RequestContext());
        $listener->onKernelRequest($event);
    }
}
