<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\Controller\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\ServiceValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ServiceValueResolverTest extends TestCase
{
    public function testDoNotSupportWhenControllerDoNotExists(): void
    {
        $resolver = new ServiceValueResolver(new ServiceLocator([]));
        $argument = new ArgumentMetadata('dummy', DummyService::class, false, false, null);
        $request = $this->requestWithAttributes(['_controller' => 'my_controller']);

        $this->assertFalse($resolver->supports($request, $argument));
    }

    public function testExistingController(): void
    {
        $resolver = new ServiceValueResolver(new ServiceLocator([
            'App\\Controller\\Mine::method' => static fn(): \Symfony\Component\DependencyInjection\ServiceLocator => new ServiceLocator([
                'dummy' => static fn(): \Symfony\Component\HttpKernel\Tests\Controller\ArgumentResolver\DummyService => new DummyService(),
            ]),
        ]));

        $request = $this->requestWithAttributes(['_controller' => 'App\\Controller\\Mine::method']);
        $argument = new ArgumentMetadata('dummy', DummyService::class, false, false, null);

        $this->assertTrue($resolver->supports($request, $argument));
        $this->assertYieldEquals([new DummyService()], $resolver->resolve($request, $argument));
    }

    public function testExistingControllerWithATrailingBackSlash(): void
    {
        $resolver = new ServiceValueResolver(new ServiceLocator([
            'App\\Controller\\Mine::method' => static fn(): \Symfony\Component\DependencyInjection\ServiceLocator => new ServiceLocator([
                'dummy' => static fn(): \Symfony\Component\HttpKernel\Tests\Controller\ArgumentResolver\DummyService => new DummyService(),
            ]),
        ]));

        $request = $this->requestWithAttributes(['_controller' => '\\App\\Controller\\Mine::method']);
        $argument = new ArgumentMetadata('dummy', DummyService::class, false, false, null);

        $this->assertTrue($resolver->supports($request, $argument));
        $this->assertYieldEquals([new DummyService()], $resolver->resolve($request, $argument));
    }

    public function testExistingControllerWithMethodNameStartUppercase(): void
    {
        $resolver = new ServiceValueResolver(new ServiceLocator([
            'App\\Controller\\Mine::method' => static fn(): \Symfony\Component\DependencyInjection\ServiceLocator => new ServiceLocator([
                'dummy' => static fn(): \Symfony\Component\HttpKernel\Tests\Controller\ArgumentResolver\DummyService => new DummyService(),
            ]),
        ]));
        $request = $this->requestWithAttributes(['_controller' => 'App\\Controller\\Mine::Method']);
        $argument = new ArgumentMetadata('dummy', DummyService::class, false, false, null);

        $this->assertTrue($resolver->supports($request, $argument));
        $this->assertYieldEquals([new DummyService()], $resolver->resolve($request, $argument));
    }

    public function testControllerNameIsAnArray(): void
    {
        $resolver = new ServiceValueResolver(new ServiceLocator([
            'App\\Controller\\Mine::method' => static fn(): \Symfony\Component\DependencyInjection\ServiceLocator => new ServiceLocator([
                'dummy' => static fn(): \Symfony\Component\HttpKernel\Tests\Controller\ArgumentResolver\DummyService => new DummyService(),
            ]),
        ]));

        $request = $this->requestWithAttributes(['_controller' => ['App\\Controller\\Mine', 'method']]);
        $argument = new ArgumentMetadata('dummy', DummyService::class, false, false, null);

        $this->assertTrue($resolver->supports($request, $argument));
        $this->assertYieldEquals([new DummyService()], $resolver->resolve($request, $argument));
    }

    private function requestWithAttributes(array $attributes)
    {
        $request = Request::create('/');

        foreach ($attributes as $name => $value) {
            $request->attributes->set($name, $value);
        }

        return $request;
    }

    private function assertYieldEquals(array $expected, \Generator $generator): void
    {
        $args = [];
        foreach ($generator as $arg) {
            $args[] = $arg;
        }

        $this->assertEquals($expected, $args);
    }
}

class DummyService
{
}
