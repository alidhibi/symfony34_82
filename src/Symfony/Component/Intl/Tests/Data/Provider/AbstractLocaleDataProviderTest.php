<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Intl\Tests\Data\Provider;

use Symfony\Component\Intl\Data\Provider\LocaleDataProvider;
use Symfony\Component\Intl\Intl;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractLocaleDataProviderTest extends AbstractDataProviderTest
{
    /**
     * @var LocaleDataProvider
     */
    protected $dataProvider;

    private $defaultLocale;

    protected function setUp()
    {
        parent::setUp();

        $this->dataProvider = new LocaleDataProvider(
            $this->getDataDirectory().'/'.Intl::LOCALE_DIR,
            $this->createEntryReader()
        );

        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->defaultLocale);
    }

    abstract protected function getDataDirectory();

    public function testGetLocales(): void
    {
        $this->assertSame($this->getLocales(), $this->dataProvider->getLocales());
    }

    public function testGetLocaleAliases(): void
    {
        $this->assertSame($this->getLocaleAliases(), $this->dataProvider->getAliases());
    }

    /**
     * @dataProvider provideLocales
     */
    public function testGetNames($displayLocale): void
    {
        $locales = array_keys($this->dataProvider->getNames($displayLocale));

        sort($locales);

        // We can't assert on exact list of locale, as there's too many variations.
        // The best we can do is to make sure getNames() returns a subset of what getLocales() returns.
        $this->assertNotEmpty($locales);
        $this->assertEmpty(array_diff($locales, $this->getLocales()));
    }

    public function testGetNamesDefaultLocale(): void
    {
        \Locale::setDefault('de_AT');

        $this->assertSame(
            $this->dataProvider->getNames('de_AT'),
            $this->dataProvider->getNames()
        );
    }

    /**
     * @dataProvider provideLocaleAliases
     */
    public function testGetNamesSupportsAliases($alias, $ofLocale): void
    {
        // Can't use assertSame(), because some aliases contain scripts with
        // different collation (=order of output) than their aliased locale
        // e.g. sr_Latn_ME => sr_ME
        $this->assertEquals(
            $this->dataProvider->getNames($ofLocale),
            $this->dataProvider->getNames($alias)
        );
    }

    /**
     * @dataProvider provideLocales
     */
    public function testGetName($displayLocale): void
    {
        $names = $this->dataProvider->getNames($displayLocale);

        foreach ($names as $locale => $name) {
            $this->assertSame($name, $this->dataProvider->getName($locale, $displayLocale));
        }
    }

    public function testGetNameDefaultLocale(): void
    {
        \Locale::setDefault('de_AT');

        $names = $this->dataProvider->getNames('de_AT');

        foreach ($names as $locale => $name) {
            $this->assertSame($name, $this->dataProvider->getName($locale));
        }
    }
}
