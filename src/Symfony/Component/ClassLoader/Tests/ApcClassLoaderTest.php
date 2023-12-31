<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ClassLoader\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\ClassLoader\ClassLoader;

/**
 * @group legacy
 */
class ApcClassLoaderTest extends TestCase
{
    protected function setUp()
    {
        if (!(filter_var(ini_get('apc.enabled'), \FILTER_VALIDATE_BOOLEAN) && filter_var(ini_get('apc.enable_cli'), \FILTER_VALIDATE_BOOLEAN))) {
            $this->markTestSkipped('The apc extension is not enabled.');
        } else {
            apcu_clear_cache();
        }
    }

    protected function tearDown()
    {
        if (filter_var(ini_get('apc.enabled'), \FILTER_VALIDATE_BOOLEAN) && filter_var(ini_get('apc.enable_cli'), \FILTER_VALIDATE_BOOLEAN)) {
            apcu_clear_cache();
        }
    }

    public function testConstructor(): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Apc\Namespaced', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');

        $loader = new ApcClassLoader('test.prefix.', $loader);

        $this->assertEquals($loader->findFile('\\' . \Apc\Namespaced\FooBar::class), apcu_fetch('test.prefix.\Apc\Namespaced\FooBar'), '__construct() takes a prefix as its first argument');
    }

    /**
     * @dataProvider getLoadClassTests
     */
    public function testLoadClass(string $className, $testClassName, string $message): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Apc\Namespaced', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Apc_Pearlike_', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');

        $loader = new ApcClassLoader('test.prefix.', $loader);
        $loader->loadClass($testClassName);
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassTests(): array
    {
        return [
           ['\\' . \Apc\Namespaced\Foo::class, \Apc\Namespaced\Foo::class,   '->loadClass() loads Apc\Namespaced\Foo class'],
           ['Apc_Pearlike_Foo',    'Apc_Pearlike_Foo',      '->loadClass() loads Apc_Pearlike_Foo class'],
        ];
    }

    /**
     * @dataProvider getLoadClassFromFallbackTests
     */
    public function testLoadClassFromFallback(string $className, $testClassName, string $message): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Apc\Namespaced', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Apc_Pearlike_', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('', [__DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/fallback']);

        $loader = new ApcClassLoader('test.prefix.fallback', $loader);
        $loader->loadClass($testClassName);

        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassFromFallbackTests(): array
    {
        return [
           ['\\' . \Apc\Namespaced\Baz::class,    \Apc\Namespaced\Baz::class,    '->loadClass() loads Apc\Namespaced\Baz class'],
           ['Apc_Pearlike_Baz',       'Apc_Pearlike_Baz',       '->loadClass() loads Apc_Pearlike_Baz class'],
           ['\\' . \Apc\Namespaced\FooBar::class, \Apc\Namespaced\FooBar::class, '->loadClass() loads Apc\Namespaced\Baz class from fallback dir'],
           ['Apc_Pearlike_FooBar',    'Apc_Pearlike_FooBar',    '->loadClass() loads Apc_Pearlike_Baz class from fallback dir'],
       ];
    }

    /**
     * @dataProvider getLoadClassNamespaceCollisionTests
     */
    public function testLoadClassNamespaceCollision(array $namespaces, $className, string $message): void
    {
        $loader = new ClassLoader();
        $loader->addPrefixes($namespaces);

        $loader = new ApcClassLoader('test.prefix.collision.', $loader);
        $loader->loadClass($className);

        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassNamespaceCollisionTests(): array
    {
        return [
           [
               [
                   'Apc\\NamespaceCollision\\A' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/alpha',
                   'Apc\\NamespaceCollision\\A\\B' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/beta',
               ],
               \Apc\NamespaceCollision\A\Foo::class,
               '->loadClass() loads NamespaceCollision\A\Foo from alpha.',
           ],
           [
               [
                   'Apc\\NamespaceCollision\\A\\B' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/beta',
                   'Apc\\NamespaceCollision\\A' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/alpha',
               ],
               \Apc\NamespaceCollision\A\Bar::class,
               '->loadClass() loads NamespaceCollision\A\Bar from alpha.',
           ],
           [
               [
                   'Apc\\NamespaceCollision\\A' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/alpha',
                   'Apc\\NamespaceCollision\\A\\B' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/beta',
               ],
               \Apc\NamespaceCollision\A\B\Foo::class,
               '->loadClass() loads NamespaceCollision\A\B\Foo from beta.',
           ],
           [
               [
                   'Apc\\NamespaceCollision\\A\\B' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/beta',
                   'Apc\\NamespaceCollision\\A' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/alpha',
               ],
               \Apc\NamespaceCollision\A\B\Bar::class,
               '->loadClass() loads NamespaceCollision\A\B\Bar from beta.',
           ],
        ];
    }

    /**
     * @dataProvider getLoadClassPrefixCollisionTests
     */
    public function testLoadClassPrefixCollision(array $prefixes, $className, string $message): void
    {
        $loader = new ClassLoader();
        $loader->addPrefixes($prefixes);

        $loader = new ApcClassLoader('test.prefix.collision.', $loader);
        $loader->loadClass($className);

        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassPrefixCollisionTests(): array
    {
        return [
           [
               [
                   'ApcPrefixCollision_A_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/alpha/Apc',
                   'ApcPrefixCollision_A_B_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/beta/Apc',
               ],
               'ApcPrefixCollision_A_Foo',
               '->loadClass() loads ApcPrefixCollision_A_Foo from alpha.',
           ],
           [
               [
                   'ApcPrefixCollision_A_B_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/beta/Apc',
                   'ApcPrefixCollision_A_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/alpha/Apc',
               ],
               'ApcPrefixCollision_A_Bar',
               '->loadClass() loads ApcPrefixCollision_A_Bar from alpha.',
           ],
           [
               [
                   'ApcPrefixCollision_A_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/alpha/Apc',
                   'ApcPrefixCollision_A_B_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/beta/Apc',
               ],
               'ApcPrefixCollision_A_B_Foo',
               '->loadClass() loads ApcPrefixCollision_A_B_Foo from beta.',
           ],
           [
               [
                   'ApcPrefixCollision_A_B_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/beta/Apc',
                   'ApcPrefixCollision_A_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/Apc/alpha/Apc',
               ],
               'ApcPrefixCollision_A_B_Bar',
               '->loadClass() loads ApcPrefixCollision_A_B_Bar from beta.',
           ],
        ];
    }
}
