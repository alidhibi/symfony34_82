<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\DataTransformer;

use PHPUnit\Framework\TestCase;

class BaseDateTimeTransformerTest extends TestCase
{
    public function testConstructFailsIfInputTimezoneIsInvalid(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('this_timezone_does_not_exist');
        $this->getMockBuilder(\Symfony\Component\Form\Extension\Core\DataTransformer\BaseDateTimeTransformer::class)->setConstructorArgs(['this_timezone_does_not_exist'])->getMock();
    }

    public function testConstructFailsIfOutputTimezoneIsInvalid(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('that_timezone_does_not_exist');
        $this->getMockBuilder(\Symfony\Component\Form\Extension\Core\DataTransformer\BaseDateTimeTransformer::class)->setConstructorArgs([null, 'that_timezone_does_not_exist'])->getMock();
    }
}
