<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\DataCollector;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestDataCollectorTest extends TestCase
{
    public function testCollect(): void
    {
        $c = new RequestDataCollector();

        $c->collect($request = $this->createRequest(), $this->createResponse());
        $c->lateCollect();

        $attributes = $c->getRequestAttributes();

        $this->assertSame('request', $c->getName());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\ParameterBag::class, $c->getRequestHeaders());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\ParameterBag::class, $c->getRequestServer());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\ParameterBag::class, $c->getRequestCookies());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\ParameterBag::class, $attributes);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\ParameterBag::class, $c->getRequestRequest());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\ParameterBag::class, $c->getRequestQuery());
        $this->assertInstanceOf(ParameterBag::class, $c->getResponseCookies());
        $this->assertSame('html', $c->getFormat());
        $this->assertEquals('foobar', $c->getRoute());
        $this->assertEquals(['name' => 'foo'], $c->getRouteParams());
        $this->assertSame([], $c->getSessionAttributes());
        $this->assertSame('en', $c->getLocale());
        $this->assertContainsEquals(__FILE__, $attributes->get('resource'));
        $this->assertSame('stdClass', $attributes->get('object')->getType());

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\ParameterBag::class, $c->getResponseHeaders());
        $this->assertSame('OK', $c->getStatusText());
        $this->assertSame(200, $c->getStatusCode());
        $this->assertSame('application/json', $c->getContentType());
    }

    public function testCollectWithoutRouteParams(): void
    {
        $request = $this->createRequest([]);

        $c = new RequestDataCollector();
        $c->collect($request, $this->createResponse());
        $c->lateCollect();

        $this->assertEquals([], $c->getRouteParams());
    }

    /**
     * @dataProvider provideControllerCallables
     */
    public function testControllerInspection(string $name, \Closure|string|static|array $callable, array $expected): void
    {
        $c = new RequestDataCollector();
        $request = $this->createRequest();
        $response = $this->createResponse();
        $this->injectController($c, $callable, $request);
        $c->collect($request, $response);
        $c->lateCollect();

        $this->assertSame($expected, $c->getController()->getValue(true), sprintf('Testing: %s', $name));
    }

    public function provideControllerCallables(): array
    {
        // make sure we always match the line number
        $r1 = new \ReflectionMethod($this, 'testControllerInspection');
        $r2 = new \ReflectionMethod($this, 'staticControllerMethod');
        $r3 = new \ReflectionClass($this);

        // test name, callable, expected
        return [
            [
                '"Regular" callable',
                fn($name, $callable, $expected) => $this->testControllerInspection($name, $callable, $expected),
                [
                    'class' => self::class,
                    'method' => 'testControllerInspection',
                    'file' => __FILE__,
                    'line' => $r1->getStartLine(),
                ],
            ],

            [
                'Closure',
                static fn(): string => 'foo',
                [
                    'class' => __NAMESPACE__.'\{closure}',
                    'method' => null,
                    'file' => __FILE__,
                    'line' => __LINE__ - 5,
                ],
            ],

            [
                'Static callback as string',
                __NAMESPACE__.'\RequestDataCollectorTest::staticControllerMethod',
                [
                    'class' => \Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::class,
                    'method' => 'staticControllerMethod',
                    'file' => __FILE__,
                    'line' => $r2->getStartLine(),
                ],
            ],

            [
                'Static callable with instance',
                fn() => $this::staticControllerMethod(),
                [
                    'class' => \Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::class,
                    'method' => 'staticControllerMethod',
                    'file' => __FILE__,
                    'line' => $r2->getStartLine(),
                ],
            ],

            [
                'Static callable with class name',
                static fn() => \Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::staticControllerMethod(),
                [
                    'class' => \Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::class,
                    'method' => 'staticControllerMethod',
                    'file' => __FILE__,
                    'line' => $r2->getStartLine(),
                ],
            ],

            [
                'Callable with instance depending on __call()',
                [$this, 'magicMethod'],
                [
                    'class' => \Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::class,
                    'method' => 'magicMethod',
                    'file' => 'n/a',
                    'line' => 'n/a',
                ],
            ],

            [
                'Callable with class name depending on __callStatic()',
                [\Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::class, 'magicMethod'],
                [
                    'class' => \Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::class,
                    'method' => 'magicMethod',
                    'file' => 'n/a',
                    'line' => 'n/a',
                ],
            ],

            [
                'Invokable controller',
                $this,
                [
                    'class' => \Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::class,
                    'method' => null,
                    'file' => __FILE__,
                    'line' => $r3->getStartLine(),
                ],
            ],
        ];
    }

    public function testItIgnoresInvalidCallables(): void
    {
        $request = $this->createRequestWithSession();
        $response = new RedirectResponse('/');

        $c = new RequestDataCollector();
        $c->collect($request, $response);

        $this->assertSame('n/a', $c->getController());
    }

    public function testItAddsRedirectedAttributesWhenRequestContainsSpecificCookie(): void
    {
        $request = $this->createRequest();
        $request->cookies->add([
            'sf_redirect' => '{}',
        ]);

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        $c = new RequestDataCollector();
        $c->onKernelResponse(new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $this->createResponse()));

        $this->assertTrue($request->attributes->get('_redirected'));
    }

    public function testItSetsARedirectCookieIfTheResponseIsARedirection(): void
    {
        $c = new RequestDataCollector();

        $response = $this->createResponse();
        $response->setStatusCode(302);

        $response->headers->set('Location', '/somewhere-else');

        $c->collect($request = $this->createRequest(), $response);
        $c->lateCollect();

        $cookie = $this->getCookieByName($response, 'sf_redirect');

        $this->assertNotEmpty($cookie->getValue());
    }

    public function testItCollectsTheRedirectionAndClearTheCookie(): void
    {
        $c = new RequestDataCollector();

        $request = $this->createRequest();
        $request->attributes->set('_redirected', true);
        $request->cookies->add([
            'sf_redirect' => '{"method": "POST"}',
        ]);

        $c->collect($request, $response = $this->createResponse());
        $c->lateCollect();

        $this->assertEquals('POST', $c->getRedirect()['method']);

        $cookie = $this->getCookieByName($response, 'sf_redirect');
        $this->assertNull($cookie->getValue());
    }

    protected function createRequest($routeParams = ['name' => 'foo'])
    {
        $request = Request::create('http://test.com/foo?bar=baz');
        $request->attributes->set('foo', 'bar');
        $request->attributes->set('_route', 'foobar');
        $request->attributes->set('_route_params', $routeParams);
        $request->attributes->set('resource', fopen(__FILE__, 'r'));
        $request->attributes->set('object', new \stdClass());

        return $request;
    }

    private function createRequestWithSession()
    {
        $request = $this->createRequest();
        $request->attributes->set('_controller', 'Foo::bar');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->getSession()->start();

        return $request;
    }

    protected function createResponse(): \Symfony\Component\HttpFoundation\Response
    {
        $response = new Response();
        $response->setStatusCode(200);

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('X-Foo-Bar', null);
        $response->headers->setCookie(new Cookie('foo', 'bar', 1, '/foo', 'localhost', true, true));
        $response->headers->setCookie(new Cookie('bar', 'foo', new \DateTime('@946684800')));
        $response->headers->setCookie(new Cookie('bazz', 'foo', '2000-12-12'));

        return $response;
    }

    /**
     * Inject the given controller callable into the data collector.
     */
    protected function injectController($collector, $controller, $request)
    {
        $resolver = $this->getMockBuilder(\Symfony\Component\HttpKernel\Controller\ControllerResolverInterface::class)->getMock();
        $httpKernel = new HttpKernel(new EventDispatcher(), $resolver, null, $this->getMockBuilder(ArgumentResolverInterface::class)->getMock());
        $event = new FilterControllerEvent($httpKernel, $controller, $request, HttpKernelInterface::MASTER_REQUEST);
        $collector->onKernelController($event);
    }

    /**
     * Dummy method used as controller callable.
     */
    public static function staticControllerMethod(): never
    {
        throw new \LogicException('Unexpected method call');
    }

    /**
     * Magic method to allow non existing methods to be called and delegated.
     */
    public function __call($method, $args)
    {
        throw new \LogicException('Unexpected method call');
    }

    /**
     * Magic method to allow non existing methods to be called and delegated.
     */
    public static function __callStatic($method, $args)
    {
        throw new \LogicException('Unexpected method call');
    }

    public function __invoke(): never
    {
        throw new \LogicException('Unexpected method call');
    }

    private function getCookieByName(Response $response, string $name)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() == $name) {
                return $cookie;
            }
        }

        throw new \InvalidArgumentException(sprintf('Cookie named "%s" is not in response', $name));
    }
}
