<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Fixtures;

class VariadicConstructorArgsDummy
{
    private readonly array $foo;

    public function __construct(...$foo)
    {
        $this->foo = $foo;
    }

    public function getFoo(): array
    {
        return $this->foo;
    }
}
