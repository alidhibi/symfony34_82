<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Mapping\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Tests\Mapping\TestClassMetadataFactory;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationLoaderTest extends TestCase
{
    private \Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader $loader;

    protected function setUp()
    {
        $this->loader = new AnnotationLoader(new AnnotationReader());
    }

    public function testInterface(): void
    {
        $this->assertInstanceOf(\Symfony\Component\Serializer\Mapping\Loader\LoaderInterface::class, $this->loader);
    }

    public function testLoadClassMetadataReturnsTrueIfSuccessful(): void
    {
        $classMetadata = new ClassMetadata(\Symfony\Component\Serializer\Tests\Fixtures\GroupDummy::class);

        $this->assertTrue($this->loader->loadClassMetadata($classMetadata));
    }

    public function testLoadGroups(): void
    {
        $classMetadata = new ClassMetadata(\Symfony\Component\Serializer\Tests\Fixtures\GroupDummy::class);
        $this->loader->loadClassMetadata($classMetadata);

        $this->assertEquals(TestClassMetadataFactory::createClassMetadata(), $classMetadata);
    }

    public function testLoadMaxDepth(): void
    {
        $classMetadata = new ClassMetadata(\Symfony\Component\Serializer\Tests\Fixtures\MaxDepthDummy::class);
        $this->loader->loadClassMetadata($classMetadata);

        $attributesMetadata = $classMetadata->getAttributesMetadata();
        $this->assertEquals(2, $attributesMetadata['foo']->getMaxDepth());
        $this->assertEquals(3, $attributesMetadata['bar']->getMaxDepth());
    }

    public function testLoadClassMetadataAndMerge(): void
    {
        $classMetadata = new ClassMetadata(\Symfony\Component\Serializer\Tests\Fixtures\GroupDummy::class);
        $parentClassMetadata = new ClassMetadata(\Symfony\Component\Serializer\Tests\Fixtures\GroupDummyParent::class);

        $this->loader->loadClassMetadata($parentClassMetadata);
        $classMetadata->merge($parentClassMetadata);

        $this->loader->loadClassMetadata($classMetadata);

        $this->assertEquals(TestClassMetadataFactory::createClassMetadata(true), $classMetadata);
    }
}
