<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Intl\ResourceBundle;

use Symfony\Component\Intl\Data\Bundle\Reader\BundleEntryReaderInterface;
use Symfony\Component\Intl\Data\Provider\CurrencyDataProvider;
use Symfony\Component\Intl\Data\Provider\LocaleDataProvider;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Default implementation of {@link CurrencyBundleInterface}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
class CurrencyBundle extends CurrencyDataProvider implements CurrencyBundleInterface
{
    private readonly \Symfony\Component\Intl\Data\Provider\LocaleDataProvider $localeProvider;

    /**
     * Creates a new currency bundle.
     *
     * @param string $path
     */
    public function __construct($path, BundleEntryReaderInterface $reader, LocaleDataProvider $localeProvider)
    {
        parent::__construct($path, $reader);

        $this->localeProvider = $localeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencySymbol($currency, $displayLocale = null)
    {
        try {
            return $this->getSymbol($currency, $displayLocale);
        } catch (MissingResourceException $missingResourceException) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencyName($currency, $displayLocale = null)
    {
        try {
            return $this->getName($currency, $displayLocale);
        } catch (MissingResourceException $missingResourceException) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencyNames($displayLocale = null)
    {
        try {
            return $this->getNames($displayLocale);
        } catch (MissingResourceException $missingResourceException) {
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFractionDigits($currency)
    {
        try {
            return parent::getFractionDigits($currency);
        } catch (MissingResourceException $missingResourceException) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRoundingIncrement($currency)
    {
        try {
            return parent::getRoundingIncrement($currency);
        } catch (MissingResourceException $missingResourceException) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocales()
    {
        try {
            return $this->localeProvider->getLocales();
        } catch (MissingResourceException $missingResourceException) {
            return [];
        }
    }
}
