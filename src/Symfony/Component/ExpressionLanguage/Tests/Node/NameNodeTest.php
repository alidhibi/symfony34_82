<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ExpressionLanguage\Tests\Node;

use Symfony\Component\ExpressionLanguage\Node\NameNode;

class NameNodeTest extends AbstractNodeTest
{
    public function getEvaluateData(): array
    {
        return [
            ['bar', new NameNode('foo'), ['foo' => 'bar']],
        ];
    }

    public function getCompileData(): array
    {
        return [
            ['$foo', new NameNode('foo')],
        ];
    }

    public function getDumpData(): array
    {
        return [
            ['foo', new NameNode('foo')],
        ];
    }
}
