<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Intl\Locale;

use Symfony\Component\Intl\Exception\MethodNotImplementedException;

/**
 * Replacement for PHP's native {@link \Locale} class.
 *
 * The only method supported in this class is {@link getDefault}. This method
 * will always return "en". All other methods will throw an exception when used.
 *
 * @author Eriksen Costa <eriksen.costa@infranology.com.br>
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
class Locale
{
    final const DEFAULT_LOCALE = null;

    /* Locale method constants */
    final const ACTUAL_LOCALE = 0;

    final const VALID_LOCALE = 1;

    /* Language tags constants */
    final const LANG_TAG = 'language';

    final const EXTLANG_TAG = 'extlang';

    final const SCRIPT_TAG = 'script';

    final const REGION_TAG = 'region';

    final const VARIANT_TAG = 'variant';

    final const GRANDFATHERED_LANG_TAG = 'grandfathered';

    final const PRIVATE_TAG = 'private';

    /**
     * Not supported. Returns the best available locale based on HTTP "Accept-Language" header according to RFC 2616.
     *
     * @param string $header The string containing the "Accept-Language" header value
     *
     * @return string The corresponding locale code
     *
     * @see https://php.net/locale.acceptfromhttp
     *
     * @throws MethodNotImplementedException
     */
    public static function acceptFromHttp($header): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns a correctly ordered and delimited locale code.
     *
     * @param array $subtags A keyed array where the keys identify the particular locale code subtag
     *
     * @return string The corresponding locale code
     *
     * @see https://php.net/locale.composelocale
     *
     * @throws MethodNotImplementedException
     */
    public static function composeLocale(array $subtags): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Checks if a language tag filter matches with locale.
     *
     * @param string $langtag      The language tag to check
     * @param string $locale       The language range to check against
     * @param bool   $canonicalize
     *
     * @return string The corresponding locale code
     *
     * @see https://php.net/locale.filtermatches
     *
     * @throws MethodNotImplementedException
     */
    public static function filterMatches($langtag, $locale, $canonicalize = false): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the variants for the input locale.
     *
     * @param string $locale The locale to extract the variants from
     *
     * @return array The locale variants
     *
     * @see https://php.net/locale.getallvariants
     *
     * @throws MethodNotImplementedException
     */
    public static function getAllVariants($locale): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Returns the default locale.
     *
     * @return string The default locale code. Always returns 'en'
     *
     * @see https://php.net/locale.getdefault
     */
    public static function getDefault(): string
    {
        return 'en';
    }

    /**
     * Not supported. Returns the localized display name for the locale language.
     *
     * @param string $locale   The locale code to return the display language from
     * @param string $inLocale Optional format locale code to use to display the language name
     *
     * @return string The localized language display name
     *
     * @see https://php.net/locale.getdisplaylanguage
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayLanguage($locale, $inLocale = null): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the localized display name for the locale.
     *
     * @param string $locale   The locale code to return the display locale name from
     * @param string $inLocale Optional format locale code to use to display the locale name
     *
     * @return string The localized locale display name
     *
     * @see https://php.net/locale.getdisplayname
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayName($locale, $inLocale = null): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the localized display name for the locale region.
     *
     * @param string $locale   The locale code to return the display region from
     * @param string $inLocale Optional format locale code to use to display the region name
     *
     * @return string The localized region display name
     *
     * @see https://php.net/locale.getdisplayregion
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayRegion($locale, $inLocale = null): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the localized display name for the locale script.
     *
     * @param string $locale   The locale code to return the display script from
     * @param string $inLocale Optional format locale code to use to display the script name
     *
     * @return string The localized script display name
     *
     * @see https://php.net/locale.getdisplayscript
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayScript($locale, $inLocale = null): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the localized display name for the locale variant.
     *
     * @param string $locale   The locale code to return the display variant from
     * @param string $inLocale Optional format locale code to use to display the variant name
     *
     * @return string The localized variant display name
     *
     * @see https://php.net/locale.getdisplayvariant
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayVariant($locale, $inLocale = null): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the keywords for the locale.
     *
     * @param string $locale The locale code to extract the keywords from
     *
     * @return array Associative array with the extracted variants
     *
     * @see https://php.net/locale.getkeywords
     *
     * @throws MethodNotImplementedException
     */
    public static function getKeywords($locale): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the primary language for the locale.
     *
     * @param string $locale The locale code to extract the language code from
     *
     * @return string|null The extracted language code or null in case of error
     *
     * @see https://php.net/locale.getprimarylanguage
     *
     * @throws MethodNotImplementedException
     */
    public static function getPrimaryLanguage($locale): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the region for the locale.
     *
     * @param string $locale The locale code to extract the region code from
     *
     * @return string|null The extracted region code or null if not present
     *
     * @see https://php.net/locale.getregion
     *
     * @throws MethodNotImplementedException
     */
    public static function getRegion($locale): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the script for the locale.
     *
     * @param string $locale The locale code to extract the script code from
     *
     * @return string|null The extracted script code or null if not present
     *
     * @see https://php.net/locale.getscript
     *
     * @throws MethodNotImplementedException
     */
    public static function getScript($locale): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the closest language tag for the locale.
     *
     * @param array  $langtag      A list of the language tags to compare to locale
     * @param string $locale       The locale to use as the language range when matching
     * @param bool   $canonicalize If true, the arguments will be converted to canonical form before matching
     * @param string $default      The locale to use if no match is found
     *
     * @see https://php.net/locale.lookup
     *
     * @throws MethodNotImplementedException
     */
    public static function lookup(array $langtag, $locale, $canonicalize = false, $default = null): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns an associative array of locale identifier subtags.
     *
     * @param string $locale The locale code to extract the subtag array from
     *
     * @return array Associative array with the extracted subtags
     *
     * @see https://php.net/locale.parselocale
     *
     * @throws MethodNotImplementedException
     */
    public static function parseLocale($locale): never
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Sets the default runtime locale.
     *
     * @param string $locale The locale code
     *
     * @return bool true on success or false on failure
     *
     * @see https://php.net/locale.setdefault
     *
     * @throws MethodNotImplementedException
     */
    public static function setDefault($locale): bool
    {
        if ('en' !== $locale) {
            throw new MethodNotImplementedException(__METHOD__);
        }

        return true;
    }
}
