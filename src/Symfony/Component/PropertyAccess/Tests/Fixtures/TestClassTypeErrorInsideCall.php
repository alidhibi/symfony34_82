<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyAccess\Tests\Fixtures;

class TestClassTypeErrorInsideCall
{
    public function expectsDateTime(\DateTime $date): void
    {
    }

    public function getProperty(): void
    {
    }

    public function setProperty($property): void
    {
        $this->expectsDateTime(null); // throws TypeError
    }
}