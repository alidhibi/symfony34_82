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
use Symfony\Component\ClassLoader\ClassLoader;

/**
 * @group legacy
 */
class ClassLoaderTest extends TestCase
{
    public function testGetPrefixes(): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Foo', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Bar', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Bas', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');

        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('Foo', $prefixes);
        $this->assertArrayNotHasKey('Foo1', $prefixes);
        $this->assertArrayHasKey('Bar', $prefixes);
        $this->assertArrayHasKey('Bas', $prefixes);
    }

    public function testGetFallbackDirs(): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix(null, __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix(null, __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');

        $fallback_dirs = $loader->getFallbackDirs();
        $this->assertCount(2, $fallback_dirs);
    }

    /**
     * @dataProvider getLoadClassTests
     */
    public function testLoadClass(string $className, $testClassName, string $message): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Namespaced2\\', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Pearlike2_', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->loadClass($testClassName);
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassTests(): array
    {
        return [
            ['\\' . \Namespaced2\Foo::class, \Namespaced2\Foo::class,   '->loadClass() loads Namespaced2\Foo class'],
            ['\\Pearlike2_Foo',    'Pearlike2_Foo',      '->loadClass() loads Pearlike2_Foo class'],
        ];
    }

    /**
     * @dataProvider getLoadNonexistentClassTests
     */
    public function testLoadNonexistentClass(string $className, string $testClassName, string $message): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Namespaced2\\', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Pearlike2_', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->loadClass($testClassName);
        $this->assertFalse(class_exists($className), $message);
    }

    public function getLoadNonexistentClassTests(): array
    {
        return [
            ['\\Pearlike3_Bar', '\\Pearlike3_Bar', '->loadClass() loads non existing Pearlike3_Bar class with a leading slash'],
        ];
    }

    public function testAddPrefixSingle(): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Foo', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Foo', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');

        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('Foo', $prefixes);
        $this->assertCount(1, $prefixes['Foo']);
    }

    public function testAddPrefixesSingle(): void
    {
        $loader = new ClassLoader();
        $loader->addPrefixes(['Foo' => ['foo', 'foo']]);
        $loader->addPrefixes(['Foo' => ['foo']]);

        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('Foo', $prefixes);
        $this->assertCount(1, $prefixes['Foo'], print_r($prefixes, true));
    }

    public function testAddPrefixMulti(): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Foo', 'foo');
        $loader->addPrefix('Foo', 'bar');

        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('Foo', $prefixes);
        $this->assertCount(2, $prefixes['Foo']);
        $this->assertContains('foo', $prefixes['Foo']);
        $this->assertContains('bar', $prefixes['Foo']);
    }

    public function testUseIncludePath(): void
    {
        $loader = new ClassLoader();
        $this->assertFalse($loader->getUseIncludePath());

        $this->assertNull($loader->findFile('Foo'));

        $includePath = get_include_path();

        $loader->setUseIncludePath(true);
        $this->assertTrue($loader->getUseIncludePath());

        set_include_path(__DIR__.'/Fixtures/includepath'.\PATH_SEPARATOR.$includePath);

        $this->assertEquals(__DIR__.\DIRECTORY_SEPARATOR.'Fixtures'.\DIRECTORY_SEPARATOR.'includepath'.\DIRECTORY_SEPARATOR.'Foo.php', $loader->findFile('Foo'));

        set_include_path($includePath);
    }

    /**
     * @dataProvider getLoadClassFromFallbackTests
     */
    public function testLoadClassFromFallback(string $className, $testClassName, string $message): void
    {
        $loader = new ClassLoader();
        $loader->addPrefix('Namespaced2\\', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('Pearlike2_', __DIR__.\DIRECTORY_SEPARATOR.'Fixtures');
        $loader->addPrefix('', [__DIR__.\DIRECTORY_SEPARATOR.'Fixtures/fallback']);
        $loader->loadClass($testClassName);
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassFromFallbackTests(): array
    {
        return [
            ['\\' . \Namespaced2\Baz::class,    \Namespaced2\Baz::class,    '->loadClass() loads Namespaced2\Baz class'],
            ['\\Pearlike2_Baz',       'Pearlike2_Baz',       '->loadClass() loads Pearlike2_Baz class'],
            ['\\' . \Namespaced2\FooBar::class, \Namespaced2\FooBar::class, '->loadClass() loads Namespaced2\Baz class from fallback dir'],
            ['\\Pearlike2_FooBar',    'Pearlike2_FooBar',    '->loadClass() loads Pearlike2_Baz class from fallback dir'],
        ];
    }

    /**
     * @dataProvider getLoadClassNamespaceCollisionTests
     */
    public function testLoadClassNamespaceCollision(array $namespaces, $className, string $message): void
    {
        $loader = new ClassLoader();
        $loader->addPrefixes($namespaces);

        $loader->loadClass($className);
        $this->assertTrue(class_exists($className), $message);
    }

    public function getLoadClassNamespaceCollisionTests(): array
    {
        return [
            [
                [
                    'NamespaceCollision\\C' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'NamespaceCollision\\C\\B' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/beta',
                ],
                \NamespaceCollision\C\Foo::class,
                '->loadClass() loads NamespaceCollision\C\Foo from alpha.',
            ],
            [
                [
                    'NamespaceCollision\\C\\B' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'NamespaceCollision\\C' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ],
                \NamespaceCollision\C\Bar::class,
                '->loadClass() loads NamespaceCollision\C\Bar from alpha.',
            ],
            [
                [
                    'NamespaceCollision\\C' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'NamespaceCollision\\C\\B' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/beta',
                ],
                \NamespaceCollision\C\B\Foo::class,
                '->loadClass() loads NamespaceCollision\C\B\Foo from beta.',
            ],
            [
                [
                    'NamespaceCollision\\C\\B' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'NamespaceCollision\\C' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ],
                \NamespaceCollision\C\B\Bar::class,
                '->loadClass() loads NamespaceCollision\C\B\Bar from beta.',
            ],
            [
                [
                    'PrefixCollision_C_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'PrefixCollision_C_B_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/beta',
                ],
                'PrefixCollision_C_Foo',
                '->loadClass() loads PrefixCollision_C_Foo from alpha.',
            ],
            [
                [
                    'PrefixCollision_C_B_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'PrefixCollision_C_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ],
                'PrefixCollision_C_Bar',
                '->loadClass() loads PrefixCollision_C_Bar from alpha.',
            ],
            [
                [
                    'PrefixCollision_C_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/alpha',
                    'PrefixCollision_C_B_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/beta',
                ],
                'PrefixCollision_C_B_Foo',
                '->loadClass() loads PrefixCollision_C_B_Foo from beta.',
            ],
            [
                [
                    'PrefixCollision_C_B_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/beta',
                    'PrefixCollision_C_' => __DIR__.\DIRECTORY_SEPARATOR.'Fixtures/alpha',
                ],
                'PrefixCollision_C_B_Bar',
                '->loadClass() loads PrefixCollision_C_B_Bar from beta.',
            ],
        ];
    }
}
