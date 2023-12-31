<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\ButtonBuilder;
use Symfony\Component\Form\Exception\InvalidArgumentException;

/**
 * @author Alexander Cheprasov <cheprasov.84@ya.ru>
 */
class ButtonBuilderTest extends TestCase
{
    public function getValidNames(): array
    {
        return [
            ['reset'],
            ['submit'],
            ['foo'],
            ['0'],
            [0],
            ['button[]'],
        ];
    }

    /**
     * @dataProvider getValidNames
     */
    public function testValidNames(string|int $name): void
    {
        $this->assertInstanceOf('\\' . \Symfony\Component\Form\ButtonBuilder::class, new ButtonBuilder($name));
    }

    public function getInvalidNames(): array
    {
        return [
            [''],
            [false],
            [null],
        ];
    }

    /**
     * @dataProvider getInvalidNames
     */
    public function testInvalidNames(string|bool|null $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Buttons cannot have empty names.');
        new ButtonBuilder($name);
    }
}
