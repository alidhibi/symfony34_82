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
use Symfony\Component\Intl\Data\Provider\LanguageDataProvider;
use Symfony\Component\Intl\Data\Provider\LocaleDataProvider;
use Symfony\Component\Intl\Data\Provider\ScriptDataProvider;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Default implementation of {@link LanguageBundleInterface}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
class LanguageBundle extends LanguageDataProvider implements LanguageBundleInterface
{
    private readonly \Symfony\Component\Intl\Data\Provider\LocaleDataProvider $localeProvider;

    private readonly \Symfony\Component\Intl\Data\Provider\ScriptDataProvider $scriptProvider;

    /**
     * Creates a new language bundle.
     *
     * @param string $path
     */
    public function __construct($path, BundleEntryReaderInterface $reader, LocaleDataProvider $localeProvider, ScriptDataProvider $scriptProvider)
    {
        parent::__construct($path, $reader);

        $this->localeProvider = $localeProvider;
        $this->scriptProvider = $scriptProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguageName($language, $region = null, $displayLocale = null)
    {
        // Some languages are translated together with their region,
        // i.e. "en_GB" is translated as "British English"
        if (null !== $region) {
            try {
                return $this->getName($language.'_'.$region, $displayLocale);
            } catch (MissingResourceException $e) {
            }
        }

        try {
            return $this->getName($language, $displayLocale);
        } catch (MissingResourceException $missingResourceException) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguageNames($displayLocale = null)
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
    public function getScriptName($script, $language = null, $displayLocale = null)
    {
        try {
            return $this->scriptProvider->getName($script, $displayLocale);
        } catch (MissingResourceException $missingResourceException) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getScriptNames($displayLocale = null)
    {
        try {
            return $this->scriptProvider->getNames($displayLocale);
        } catch (MissingResourceException $missingResourceException) {
            return [];
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
