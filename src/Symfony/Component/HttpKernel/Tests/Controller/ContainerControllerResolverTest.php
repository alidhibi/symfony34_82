<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\Controller;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;

class ContainerControllerResolverTest extends ControllerResolverTest
{
    public function testGetControllerService(): void
    {
        $container = $this->createMockContainer();
        $container->expects($this->once())
            ->method('has')
            ->with('foo')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this)
        ;

        $resolver = $this->createControllerResolver(null, $container);
        $request = Request::create('/');
        $request->attributes->set('_controller', 'foo:controllerMethod1');

        $controller = $resolver->getController($request);

        $this->assertInstanceOf(static::class, $controller[0]);
        $this->assertSame('controllerMethod1', $controller[1]);
    }

    public function testGetControllerInvokableService(): void
    {
        $invokableController = new InvokableController('bar');

        $container = $this->createMockContainer();
        $container->expects($this->once())
            ->method('has')
            ->with('foo')
            ->willReturn(true)
        ;
        $container->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($invokableController)
        ;

        $resolver = $this->createControllerResolver(null, $container);
        $request = Request::create('/');
        $request->attributes->set('_controller', 'foo');

        $controller = $resolver->getController($request);

        $this->assertEquals($invokableController, $controller);
    }

    public function testGetControllerInvokableServiceWithClassNameAsName(): void
    {
        $invokableController = new InvokableController('bar');
        $className = InvokableController::class;

        $container = $this->createMockContainer();
        $container->expects($this->once())
            ->method('has')
            ->with($className)
            ->willReturn(true)
        ;
        $container->expects($this->once())
            ->method('get')
            ->with($className)
            ->willReturn($invokableController)
        ;

        $resolver = $this->createControllerResolver(null, $container);
        $request = Request::create('/');
        $request->attributes->set('_controller', $className);

        $controller = $resolver->getController($request);

        $this->assertEquals($invokableController, $controller);
    }

    public function testNonInstantiableController(): void
    {
        $container = $this->createMockContainer();
        $container->expects($this->once())
            ->method('has')
            ->with(NonInstantiableController::class)
            ->willReturn(false)
        ;

        $resolver = $this->createControllerResolver(null, $container);
        $request = Request::create('/');
        $request->attributes->set('_controller', static fn() => \Symfony\Component\HttpKernel\Tests\Controller\NonInstantiableController::action());

        $controller = $resolver->getController($request);

        $this->assertSame(static fn() => \Symfony\Component\HttpKernel\Tests\Controller\NonInstantiableController::action(), $controller);
    }

    public function testNonConstructController(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Controller "Symfony\Component\HttpKernel\Tests\Controller\ImpossibleConstructController" cannot be fetched from the container because it is private. Did you forget to tag the service with "controller.service_arguments"?');
        $container = $this->getMockBuilder(Container::class)->getMock();
        $container->expects($this->exactly(2))
            ->method('has')
            ->with(ImpossibleConstructController::class)
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $container->expects($this->atLeastOnce())
            ->method('getRemovedIds')
            ->with()
            ->willReturn([ImpossibleConstructController::class => true])
        ;

        $this->createControllerResolver(null, $container);
        $request = Request::create('/');
        $request->attributes->set('_controller', [ImpossibleConstructController::class, 'action']);
    }

    public function testNonInstantiableControllerWithCorrespondingService(): void
    {
        $service = new \stdClass();

        $container = $this->createMockContainer();
        $container->expects($this->atLeastOnce())
            ->method('has')
            ->with(NonInstantiableController::class)
            ->willReturn(true)
        ;
        $container->expects($this->atLeastOnce())
            ->method('get')
            ->with(NonInstantiableController::class)
            ->willReturn($service)
        ;

        $resolver = $this->createControllerResolver(null, $container);
        $request = Request::create('/');
        $request->attributes->set('_controller', static fn() => \Symfony\Component\HttpKernel\Tests\Controller\NonInstantiableController::action());

        $controller = $resolver->getController($request);

        $this->assertSame([$service, 'action'], $controller);
    }

    public function testExceptionWhenUsingRemovedControllerService(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Controller "app.my_controller" cannot be fetched from the container because it is private. Did you forget to tag the service with "controller.service_arguments"?');

        $container = new ContainerBuilder();
        $container->register('app.my_controller');
        $container->removeDefinition('app.my_controller');

        $resolver = $this->createControllerResolver(null, $container);

        $request = Request::create('/');
        $request->attributes->set('_controller', 'app.my_controller');
        $resolver->getController($request);
    }

    public function testExceptionWhenUsingControllerWithoutAnInvokeMethod(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Controller "app.my_controller" cannot be called without a method name. Did you forget an "__invoke" method?');
        $container = $this->getMockBuilder(Container::class)->getMock();
        $container->expects($this->once())
            ->method('has')
            ->with('app.my_controller')
            ->willReturn(true)
        ;
        $container->expects($this->once())
            ->method('get')
            ->with('app.my_controller')
            ->willReturn(new ImpossibleConstructController('toto', 'controller'))
        ;

        $resolver = $this->createControllerResolver(null, $container);

        $request = Request::create('/');
        $request->attributes->set('_controller', 'app.my_controller');
        $resolver->getController($request);
    }

    /**
     * @dataProvider getUndefinedControllers
     */
    public function testGetControllerOnNonUndefinedFunction(string $controller, string $exceptionName = null, string $exceptionMessage = null): void
    {
        // All this logic needs to be duplicated, since calling parent::testGetControllerOnNonUndefinedFunction will override the expected exception and not use the regex
        $resolver = $this->createControllerResolver();
        $this->expectException($exceptionName);
        $this->expectExceptionMessageMatches($exceptionMessage);

        $request = Request::create('/');
        $request->attributes->set('_controller', $controller);
        $resolver->getController($request);
    }

    public function getUndefinedControllers(): array
    {
        return [
            ['foo', \LogicException::class, '/Controller not found: service "foo" does not exist\./'],
            ['oof::bar', \InvalidArgumentException::class, '/Class "oof" does not exist\./'],
            ['stdClass', \LogicException::class, '/Controller not found: service "stdClass" does not exist\./'],
            [
                \Symfony\Component\HttpKernel\Tests\Controller\ControllerResolverTest::class . '::bar',
                \InvalidArgumentException::class,
                '/.?[cC]ontroller(.*?) for URI "\/" is not callable:( Expected method(.*) Available methods)?/',
            ],
        ];
    }

    protected function createControllerResolver(LoggerInterface $logger = null, ContainerInterface $container = null): \Symfony\Component\HttpKernel\Controller\ContainerControllerResolver
    {
        if (!$container instanceof \Psr\Container\ContainerInterface) {
            $container = $this->createMockContainer();
        }

        return new ContainerControllerResolver($container, $logger);
    }

    protected function createMockContainer()
    {
        return $this->getMockBuilder(ContainerInterface::class)->getMock();
    }
}

class InvokableController
{
    public function __invoke()
    {
    }
}

abstract class NonInstantiableController
{
    public static function action(): void
    {
    }
}

class ImpossibleConstructController
{
    public function action(): void
    {
    }
}
