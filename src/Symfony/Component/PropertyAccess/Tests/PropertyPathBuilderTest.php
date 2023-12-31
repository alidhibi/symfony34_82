<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyAccess\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathBuilder;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PropertyPathBuilderTest extends TestCase
{
    final const PREFIX = 'old1[old2].old3[old4][old5].old6';

    private \Symfony\Component\PropertyAccess\PropertyPathBuilder $builder;

    protected function setUp()
    {
        $this->builder = new PropertyPathBuilder(new PropertyPath(self::PREFIX));
    }

    public function testCreateEmpty(): void
    {
        $builder = new PropertyPathBuilder();

        $this->assertNull($builder->getPropertyPath());
    }

    public function testCreateCopyPath(): void
    {
        $this->assertEquals(new PropertyPath(self::PREFIX), $this->builder->getPropertyPath());
    }

    public function testAppendIndex(): void
    {
        $this->builder->appendIndex('new1');

        $path = new PropertyPath(self::PREFIX.'[new1]');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testAppendProperty(): void
    {
        $this->builder->appendProperty('new1');

        $path = new PropertyPath(self::PREFIX.'.new1');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testAppend(): void
    {
        $this->builder->append(new PropertyPath('new1[new2]'));

        $path = new PropertyPath(self::PREFIX.'.new1[new2]');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testAppendUsingString(): void
    {
        $this->builder->append('new1[new2]');

        $path = new PropertyPath(self::PREFIX.'.new1[new2]');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testAppendWithOffset(): void
    {
        $this->builder->append(new PropertyPath('new1[new2].new3'), 1);

        $path = new PropertyPath(self::PREFIX.'[new2].new3');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testAppendWithOffsetAndLength(): void
    {
        $this->builder->append(new PropertyPath('new1[new2].new3'), 1, 1);

        $path = new PropertyPath(self::PREFIX.'[new2]');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceByIndex(): void
    {
        $this->builder->replaceByIndex(1, 'new1');

        $path = new PropertyPath('old1[new1].old3[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceByIndexWithoutName(): void
    {
        $this->builder->replaceByIndex(0);

        $path = new PropertyPath('[old1][old2].old3[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceByIndexDoesNotAllowInvalidOffsets(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->builder->replaceByIndex(6, 'new1');
    }

    public function testReplaceByIndexDoesNotAllowNegativeOffsets(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->builder->replaceByIndex(-1, 'new1');
    }

    public function testReplaceByProperty(): void
    {
        $this->builder->replaceByProperty(1, 'new1');

        $path = new PropertyPath('old1.new1.old3[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceByPropertyWithoutName(): void
    {
        $this->builder->replaceByProperty(1);

        $path = new PropertyPath('old1.old2.old3[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceByPropertyDoesNotAllowInvalidOffsets(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->builder->replaceByProperty(6, 'new1');
    }

    public function testReplaceByPropertyDoesNotAllowNegativeOffsets(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->builder->replaceByProperty(-1, 'new1');
    }

    public function testReplace(): void
    {
        $this->builder->replace(1, 1, new PropertyPath('new1[new2].new3'));

        $path = new PropertyPath('old1.new1[new2].new3.old3[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceUsingString(): void
    {
        $this->builder->replace(1, 1, 'new1[new2].new3');

        $path = new PropertyPath('old1.new1[new2].new3.old3[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceNegative(): void
    {
        $this->builder->replace(-1, 1, new PropertyPath('new1[new2].new3'));

        $path = new PropertyPath('old1[old2].old3[old4][old5].new1[new2].new3');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    /**
     * @dataProvider provideInvalidOffsets
     */
    public function testReplaceDoesNotAllowInvalidOffsets(int $offset): void
    {
        $this->expectException('OutOfBoundsException');
        $this->builder->replace($offset, 1, new PropertyPath('new1[new2].new3'));
    }

    public function provideInvalidOffsets(): array
    {
        return [
            [6],
            [-7],
        ];
    }

    public function testReplaceWithLengthGreaterOne(): void
    {
        $this->builder->replace(0, 2, new PropertyPath('new1[new2].new3'));

        $path = new PropertyPath('new1[new2].new3.old3[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceSubstring(): void
    {
        $this->builder->replace(1, 1, new PropertyPath('new1[new2].new3.new4[new5]'), 1, 3);

        $path = new PropertyPath('old1[new2].new3.new4.old3[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testReplaceSubstringWithLengthGreaterOne(): void
    {
        $this->builder->replace(1, 2, new PropertyPath('new1[new2].new3.new4[new5]'), 1, 3);

        $path = new PropertyPath('old1[new2].new3.new4[old4][old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    // https://github.com/symfony/symfony/issues/5605
    public function testReplaceWithLongerPath(): void
    {
        // error occurs when path contains at least two more elements
        // than the builder
        $path = new PropertyPath('new1.new2.new3');

        $builder = new PropertyPathBuilder(new PropertyPath('old1'));
        $builder->replace(0, 1, $path);

        $this->assertEquals($path, $builder->getPropertyPath());
    }

    public function testReplaceWithLongerPathKeepsOrder(): void
    {
        $path = new PropertyPath('new1.new2.new3');
        $expected = new PropertyPath('new1.new2.new3.old2');

        $builder = new PropertyPathBuilder(new PropertyPath('old1.old2'));
        $builder->replace(0, 1, $path);

        $this->assertEquals($expected, $builder->getPropertyPath());
    }

    public function testRemove(): void
    {
        $this->builder->remove(3);

        $path = new PropertyPath('old1[old2].old3[old5].old6');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }

    public function testRemoveDoesNotAllowInvalidOffsets(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->builder->remove(6);
    }

    public function testRemoveDoesNotAllowNegativeOffsets(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->builder->remove(-1);
    }

    public function testRemoveAndAppendAtTheEnd(): void
    {
        $this->builder->remove($this->builder->getLength() - 1);

        $path = new PropertyPath('old1[old2].old3[old4][old5]');

        $this->assertEquals($path, $this->builder->getPropertyPath());

        $this->builder->appendProperty('old7');

        $path = new PropertyPath('old1[old2].old3[old4][old5].old7');

        $this->assertEquals($path, $this->builder->getPropertyPath());

        $this->builder->remove($this->builder->getLength() - 1);

        $path = new PropertyPath('old1[old2].old3[old4][old5]');

        $this->assertEquals($path, $this->builder->getPropertyPath());
    }
}
