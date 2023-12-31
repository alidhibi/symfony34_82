<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Finder\Tests\Comparator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Comparator\Comparator;

class ComparatorTest extends TestCase
{
    public function testGetSetOperator(): void
    {
        $comparator = new Comparator();
        try {
            $comparator->setOperator('foo');
            $this->fail('->setOperator() throws an \InvalidArgumentException if the operator is not valid.');
        } catch (\Exception $exception) {
            $this->assertInstanceOf('InvalidArgumentException', $exception, '->setOperator() throws an \InvalidArgumentException if the operator is not valid.');
        }

        $comparator = new Comparator();
        $comparator->setOperator('>');
        $this->assertEquals('>', $comparator->getOperator(), '->getOperator() returns the current operator');
    }

    public function testGetSetTarget(): void
    {
        $comparator = new Comparator();
        $comparator->setTarget(8);
        $this->assertEquals(8, $comparator->getTarget(), '->getTarget() returns the target');
    }

    /**
     * @dataProvider getTestData
     */
    public function testTest(string $operator, string $target, array $match, array $noMatch): void
    {
        $c = new Comparator();
        $c->setOperator($operator);
        $c->setTarget($target);

        foreach ($match as $m) {
            $this->assertTrue($c->test($m), '->test() tests a string against the expression');
        }

        foreach ($noMatch as $m) {
            $this->assertFalse($c->test($m), '->test() tests a string against the expression');
        }
    }

    public function getTestData(): array
    {
        return [
            ['<', '1000', ['500', '999'], ['1000', '1500']],
        ];
    }
}
