<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Asset\Tests\VersionStrategy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

class StaticVersionStrategyTest extends TestCase
{
    public function testGetVersion(): void
    {
        $version = 'v1';
        $path = 'test-path';
        $staticVersionStrategy = new StaticVersionStrategy($version);
        $this->assertSame($version, $staticVersionStrategy->getVersion($path));
    }

    /**
     * @dataProvider getConfigs
     */
    public function testApplyVersion(string $path, string $version, ?string $format): void
    {
        $staticVersionStrategy = new StaticVersionStrategy($version, $format);
        $formatted = sprintf($format ?: '%s?%s', $path, $version);
        $this->assertSame($formatted, $staticVersionStrategy->applyVersion($path));
    }

    public function getConfigs(): array
    {
        return [
            ['test-path', 'v1', null],
            ['test-path', 'v2', '%s?test%s'],
        ];
    }
}
