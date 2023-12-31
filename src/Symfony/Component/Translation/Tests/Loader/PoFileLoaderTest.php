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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Translation\Loader\PoFileLoader;

class PoFileLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/resources.po';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo'], $catalogue->all('domain1'));
        $this->assertEquals('en', $catalogue->getLocale());
        $this->assertEquals([new FileResource($resource)], $catalogue->getResources());
    }

    public function testLoadPlurals(): void
    {
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/plurals.po';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $this->assertEquals([
            'foo|foos' => 'bar|bars',
            '{0} no foos|one foo|%count% foos' => '{0} no bars|one bar|%count% bars',
        ], $catalogue->all('domain1'));
        $this->assertEquals('en', $catalogue->getLocale());
        $this->assertEquals([new FileResource($resource)], $catalogue->getResources());
    }

    public function testLoadDoesNothingIfEmpty(): void
    {
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/empty.po';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $this->assertEquals([], $catalogue->all('domain1'));
        $this->assertEquals('en', $catalogue->getLocale());
        $this->assertEquals([new FileResource($resource)], $catalogue->getResources());
    }

    public function testLoadNonExistingResource(): void
    {
        $this->expectException(\Symfony\Component\Translation\Exception\NotFoundResourceException::class);
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/non-existing.po';
        $loader->load($resource, 'en', 'domain1');
    }

    public function testLoadEmptyTranslation(): void
    {
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/empty-translation.po';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $this->assertEquals(['foo' => ''], $catalogue->all('domain1'));
        $this->assertEquals('en', $catalogue->getLocale());
        $this->assertEquals([new FileResource($resource)], $catalogue->getResources());
    }

    public function testEscapedId(): void
    {
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/escaped-id.po';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $messages = $catalogue->all('domain1');
        $this->assertArrayHasKey('escaped "foo"', $messages);
        $this->assertEquals('escaped "bar"', $messages['escaped "foo"']);
    }

    public function testEscapedIdPlurals(): void
    {
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/escaped-id-plurals.po';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $messages = $catalogue->all('domain1');
        $this->assertArrayHasKey('escaped "foo"|escaped "foos"', $messages);
        $this->assertEquals('escaped "bar"|escaped "bars"', $messages['escaped "foo"|escaped "foos"']);
    }

    public function testSkipFuzzyTranslations(): void
    {
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/fuzzy-translations.po';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $messages = $catalogue->all('domain1');
        $this->assertArrayHasKey('foo1', $messages);
        $this->assertArrayNotHasKey('foo2', $messages);
        $this->assertArrayHasKey('foo3', $messages);
    }

    public function testMissingPlurals(): void
    {
        $loader = new PoFileLoader();
        $resource = __DIR__.'/../fixtures/missing-plurals.po';
        $catalogue = $loader->load($resource, 'en', 'domain1');

        $this->assertEquals([
            'foo|foos' => '-|bar|-|bars',
        ], $catalogue->all('domain1'));
        $this->assertEquals('en', $catalogue->getLocale());
    }
}
