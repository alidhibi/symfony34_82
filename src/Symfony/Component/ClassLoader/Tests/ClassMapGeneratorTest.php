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
use Symfony\Component\ClassLoader\ClassMapGenerator;

/**
 * @group legacy
 */
class ClassMapGeneratorTest extends TestCase
{
    /**
     * @var string|null
     */
    private string|bool|null $workspace = null;

    public function prepare_workspace(): void
    {
        $this->workspace = sys_get_temp_dir().'/'.microtime(true).'.'.mt_rand();
        mkdir($this->workspace, 0777, true);
        $this->workspace = realpath($this->workspace);
    }

    /**
     * @param string $file
     */
    private function clean(string|\SplFileInfo|bool|null $file): void
    {
        if (is_dir($file) && !is_link($file)) {
            $dir = new \FilesystemIterator($file);
            foreach ($dir as $childFile) {
                $this->clean($childFile);
            }

            rmdir($file);
        } else {
            unlink($file);
        }
    }

    /**
     * @dataProvider getTestCreateMapTests
     */
    public function testDump($directory): void
    {
        $this->prepare_workspace();

        $file = $this->workspace.'/file';

        $generator = new ClassMapGenerator();
        $generator->dump($directory, $file);
        $this->assertFileExists($file);

        $this->clean($this->workspace);
    }

    /**
     * @dataProvider getTestCreateMapTests
     */
    public function testCreateMap($directory, array $expected): void
    {
        $this->assertEqualsNormalized($expected, ClassMapGenerator::createMap($directory));
    }

    public function getTestCreateMapTests(): array
    {
        return [
            [__DIR__.'/Fixtures/Namespaced', [
                \Namespaced\Bar::class => realpath(__DIR__).'/Fixtures/Namespaced/Bar.php',
                \Namespaced\Foo::class => realpath(__DIR__).'/Fixtures/Namespaced/Foo.php',
                \Namespaced\Baz::class => realpath(__DIR__).'/Fixtures/Namespaced/Baz.php',
                \Namespaced\WithComments::class => realpath(__DIR__).'/Fixtures/Namespaced/WithComments.php',
                \Namespaced\WithStrictTypes::class => realpath(__DIR__).'/Fixtures/Namespaced/WithStrictTypes.php',
                \Namespaced\WithHaltCompiler::class => realpath(__DIR__).'/Fixtures/Namespaced/WithHaltCompiler.php',
                \Namespaced\WithDirMagic::class => realpath(__DIR__).'/Fixtures/Namespaced/WithDirMagic.php',
                \Namespaced\WithFileMagic::class => realpath(__DIR__).'/Fixtures/Namespaced/WithFileMagic.php',
            ]],
            [__DIR__.'/Fixtures/beta/NamespaceCollision', [
                \NamespaceCollision\A\B\Bar::class => realpath(__DIR__).'/Fixtures/beta/NamespaceCollision/A/B/Bar.php',
                \NamespaceCollision\A\B\Foo::class => realpath(__DIR__).'/Fixtures/beta/NamespaceCollision/A/B/Foo.php',
                \NamespaceCollision\C\B\Bar::class => realpath(__DIR__).'/Fixtures/beta/NamespaceCollision/C/B/Bar.php',
                \NamespaceCollision\C\B\Foo::class => realpath(__DIR__).'/Fixtures/beta/NamespaceCollision/C/B/Foo.php',
            ]],
            [__DIR__.'/Fixtures/Pearlike', [
                'Pearlike_Foo' => realpath(__DIR__).'/Fixtures/Pearlike/Foo.php',
                'Pearlike_Bar' => realpath(__DIR__).'/Fixtures/Pearlike/Bar.php',
                'Pearlike_Baz' => realpath(__DIR__).'/Fixtures/Pearlike/Baz.php',
                'Pearlike_WithComments' => realpath(__DIR__).'/Fixtures/Pearlike/WithComments.php',
            ]],
            [__DIR__.'/Fixtures/classmap', [
                \Foo\Bar\A::class => realpath(__DIR__).'/Fixtures/classmap/sameNsMultipleClasses.php',
                \Foo\Bar\B::class => realpath(__DIR__).'/Fixtures/classmap/sameNsMultipleClasses.php',
                'A' => realpath(__DIR__).'/Fixtures/classmap/multipleNs.php',
                \Alpha\A::class => realpath(__DIR__).'/Fixtures/classmap/multipleNs.php',
                \Alpha\B::class => realpath(__DIR__).'/Fixtures/classmap/multipleNs.php',
                \Beta\A::class => realpath(__DIR__).'/Fixtures/classmap/multipleNs.php',
                \Beta\B::class => realpath(__DIR__).'/Fixtures/classmap/multipleNs.php',
                \ClassMap\SomeInterface::class => realpath(__DIR__).'/Fixtures/classmap/SomeInterface.php',
                \ClassMap\SomeParent::class => realpath(__DIR__).'/Fixtures/classmap/SomeParent.php',
                \ClassMap\SomeClass::class => realpath(__DIR__).'/Fixtures/classmap/SomeClass.php',
            ]],
            [__DIR__.'/Fixtures/php5.4', [
                'TFoo' => __DIR__.'/Fixtures/php5.4/traits.php',
                'CFoo' => __DIR__.'/Fixtures/php5.4/traits.php',
                \Foo\TBar::class => __DIR__.'/Fixtures/php5.4/traits.php',
                \Foo\IBar::class => __DIR__.'/Fixtures/php5.4/traits.php',
                \Foo\TFooBar::class => __DIR__.'/Fixtures/php5.4/traits.php',
                \Foo\CBar::class => __DIR__.'/Fixtures/php5.4/traits.php',
            ]],
            [__DIR__.'/Fixtures/php5.5', [
                \ClassCons\Foo::class => __DIR__.'/Fixtures/php5.5/class_cons.php',
            ]],
        ];
    }

    public function testCreateMapFinderSupport(): void
    {
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in(__DIR__.'/Fixtures/beta/NamespaceCollision');

        $this->assertEqualsNormalized([
            \NamespaceCollision\A\B\Bar::class => realpath(__DIR__).'/Fixtures/beta/NamespaceCollision/A/B/Bar.php',
            \NamespaceCollision\A\B\Foo::class => realpath(__DIR__).'/Fixtures/beta/NamespaceCollision/A/B/Foo.php',
            \NamespaceCollision\C\B\Bar::class => realpath(__DIR__).'/Fixtures/beta/NamespaceCollision/C/B/Bar.php',
            \NamespaceCollision\C\B\Foo::class => realpath(__DIR__).'/Fixtures/beta/NamespaceCollision/C/B/Foo.php',
        ], ClassMapGenerator::createMap($finder));
    }

    protected function assertEqualsNormalized(array $expected, array $actual, $message = '')
    {
        foreach ($expected as $ns => $path) {
            $expected[$ns] = str_replace('\\', '/', $path);
        }

        foreach ($actual as $ns => $path) {
            $actual[$ns] = str_replace('\\', '/', $path);
        }

        $this->assertEquals($expected, $actual, $message);
    }
}
