<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributeMetadataTest extends TestCase
{
    public function testInterface(): void
    {
        $attributeMetadata = new AttributeMetadata('name');
        $this->assertInstanceOf(\Symfony\Component\Serializer\Mapping\AttributeMetadataInterface::class, $attributeMetadata);
    }

    public function testGetName(): void
    {
        $attributeMetadata = new AttributeMetadata('name');
        $this->assertEquals('name', $attributeMetadata->getName());
    }

    public function testGroups(): void
    {
        $attributeMetadata = new AttributeMetadata('group');
        $attributeMetadata->addGroup('a');
        $attributeMetadata->addGroup('a');
        $attributeMetadata->addGroup('b');

        $this->assertEquals(['a', 'b'], $attributeMetadata->getGroups());
    }

    public function testMaxDepth(): void
    {
        $attributeMetadata = new AttributeMetadata('name');
        $attributeMetadata->setMaxDepth(69);

        $this->assertEquals(69, $attributeMetadata->getMaxDepth());
    }

    public function testMerge(): void
    {
        $attributeMetadata1 = new AttributeMetadata('a1');
        $attributeMetadata1->addGroup('a');
        $attributeMetadata1->addGroup('b');

        $attributeMetadata2 = new AttributeMetadata('a2');
        $attributeMetadata2->addGroup('a');
        $attributeMetadata2->addGroup('c');
        $attributeMetadata2->setMaxDepth(2);

        $attributeMetadata1->merge($attributeMetadata2);

        $this->assertEquals(['a', 'b', 'c'], $attributeMetadata1->getGroups());
        $this->assertEquals(2, $attributeMetadata1->getMaxDepth());
    }

    public function testSerialize(): void
    {
        $attributeMetadata = new AttributeMetadata('attribute');
        $attributeMetadata->addGroup('a');
        $attributeMetadata->addGroup('b');
        $attributeMetadata->setMaxDepth(3);

        $serialized = serialize($attributeMetadata);
        $this->assertEquals($attributeMetadata, unserialize($serialized));
    }
}
