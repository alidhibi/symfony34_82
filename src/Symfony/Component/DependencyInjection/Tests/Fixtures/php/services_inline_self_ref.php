<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/**
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 *
 * @final since Symfony 3.3
 */
class Symfony_DI_PhpDumper_Test_Inline_Self_Ref extends Container
{

    public function __construct()
    {
        $this->services = [];
        $this->normalizedIds = [
            'app\\foo' => 'App\\Foo',
        ];
        $this->methodMap = [
            'App\\Foo' => 'getFooService',
        ];

        $this->aliases = [];
    }

    public function getRemovedIds(): array
    {
        return [
            \Psr\Container\ContainerInterface::class => true,
            \Symfony\Component\DependencyInjection\ContainerInterface::class => true,
        ];
    }

    public function compile(): never
    {
        throw new LogicException('You cannot compile a dumped container that was already compiled.');
    }

    public function isCompiled(): bool
    {
        return true;
    }

    public function isFrozen(): bool
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the isCompiled() method instead.', __METHOD__), E_USER_DEPRECATED);

        return true;
    }

    /**
     * Gets the public 'App\Foo' shared service.
     *
     * @return \App\Foo
     */
    protected function getFooService()
    {
        $a = new \App\Bar();

        $b = new \App\Baz($a);
        $b->bar = $a;

        $this->services['App\\Foo'] = $instance = new \App\Foo($b);

        $a->foo = $instance;

        return $instance;
    }
}
