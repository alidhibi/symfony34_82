<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Translation\Tests\Loader;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Translation\Loader\IcuResFileLoader;

/**
 * @requires extension intl
 */
class IcuResFileLoaderTest extends LocalizedTestCase
{
    public function testLoad(): void
    {
        // resource is build using genrb command
        $loader = new IcuResFileLoader();
        $resource = __DIR__.'/../fixtures/resourcebundle/res';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $this->assertEquals(['foo' => 'bar'], $catalogue->all('domain1'));
        $this->assertEquals('en', $catalogue->getLocale());
        $this->assertEquals([new DirectoryResource($resource)], $catalogue->getResources());
    }

    public function testLoadNonExistingResource(): void
    {
        $this->expectException(\Symfony\Component\Translation\Exception\NotFoundResourceException::class);
        $loader = new IcuResFileLoader();
        $loader->load(__DIR__.'/../fixtures/non-existing.txt', 'en', 'domain1');
    }

    public function testLoadInvalidResource(): void
    {
        $this->expectException(\Symfony\Component\Translation\Exception\InvalidResourceException::class);
        $loader = new IcuResFileLoader();
        $loader->load(__DIR__.'/../fixtures/resourcebundle/corrupted', 'en', 'domain1');
    }
}
