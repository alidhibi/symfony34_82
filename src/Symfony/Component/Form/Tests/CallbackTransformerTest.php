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
use Symfony\Component\Form\CallbackTransformer;

class CallbackTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new CallbackTransformer(
            static fn($value): string => $value.' has been transformed',
            static fn($value): string => $value.' has reversely been transformed'
        );

        $this->assertEquals('foo has been transformed', $transformer->transform('foo'));
        $this->assertEquals('bar has reversely been transformed', $transformer->reverseTransform('bar'));
    }
}