<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\CacheWarmer;

use Symfony\Component\ClassLoader\ClassCollectionLoader;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Generates the Class Cache (classes.php) file.
 *
 * @author Tugdual Saunier <tucksaun@gmail.com>
 *
 * @deprecated since version 3.3, to be removed in 4.0.
 */
class ClassCacheCacheWarmer implements CacheWarmerInterface
{
    private ?array $declaredClasses = null;

    public function __construct(array $declaredClasses = null)
    {
        @trigger_error('The '.__CLASS__.' class is deprecated since Symfony 3.3 and will be removed in 4.0.', \E_USER_DEPRECATED);

        $this->declaredClasses = $declaredClasses;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp(string $cacheDir): void
    {
        $classmap = $cacheDir.'/classes.map';

        if (!is_file($classmap)) {
            return;
        }

        if (file_exists($cacheDir.'/classes.php')) {
            return;
        }

        $declared = null !== $this->declaredClasses ? $this->declaredClasses : [...get_declared_classes(), ...get_declared_interfaces(), ...get_declared_traits()];

        ClassCollectionLoader::inline(include($classmap), $cacheDir.'/classes.php', $declared);
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * @return bool always true
     */
    public function isOptional(): bool
    {
        return true;
    }
}
