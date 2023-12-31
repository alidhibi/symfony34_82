<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ClassLoader;

@trigger_error('The '.__NAMESPACE__.'\ClassLoader class is deprecated since Symfony 3.3 and will be removed in 4.0. Use Composer instead.', \E_USER_DEPRECATED);

/**
 * ClassLoader implements an PSR-0 class loader.
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 *
 *     $loader = new ClassLoader();
 *
 *     // register classes with namespaces
 *     $loader->addPrefix('Symfony\Component', __DIR__.'/component');
 *     $loader->addPrefix('Symfony',           __DIR__.'/framework');
 *
 *     // activate the autoloader
 *     $loader->register();
 *
 *     // to enable searching the include path (e.g. for PEAR packages)
 *     $loader->setUseIncludePath(true);
 *
 * In this example, if you try to use a class in the Symfony\Component
 * namespace or one of its children (Symfony\Component\Console for instance),
 * the autoloader will first look for the class under the component/
 * directory, and it will then fallback to the framework/ directory if not
 * found before giving up.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 *
 * @deprecated since version 3.3, to be removed in 4.0.
 */
class ClassLoader
{
    private array $prefixes = [];

    private array $fallbackDirs = [];

    private bool $useIncludePath = false;

    /**
     * Returns prefixes.
     *
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * Returns fallback directories.
     *
     */
    public function getFallbackDirs(): array
    {
        return $this->fallbackDirs;
    }

    /**
     * Adds prefixes.
     *
     * @param array $prefixes Prefixes to add
     */
    public function addPrefixes(array $prefixes): void
    {
        foreach ($prefixes as $prefix => $path) {
            $this->addPrefix($prefix, $path);
        }
    }

    /**
     * Registers a set of classes.
     *
     * @param string       $prefix The classes prefix
     * @param array|string $paths  The location(s) of the classes
     */
    public function addPrefix($prefix, $paths): void
    {
        if ($prefix === '' || $prefix === '0') {
            foreach ((array) $paths as $path) {
                $this->fallbackDirs[] = $path;
            }

            return;
        }

        if (isset($this->prefixes[$prefix])) {
            if (\is_array($paths)) {
                $this->prefixes[$prefix] = array_unique(array_merge(
                    $this->prefixes[$prefix],
                    $paths
                ));
            } elseif (!\in_array($paths, $this->prefixes[$prefix])) {
                $this->prefixes[$prefix][] = $paths;
            }
        } else {
            $this->prefixes[$prefix] = array_unique((array) $paths);
        }
    }

    /**
     * Turns on searching the include for class files.
     *
     * @param bool $useIncludePath
     */
    public function setUseIncludePath($useIncludePath): void
    {
        $this->useIncludePath = (bool) $useIncludePath;
    }

    /**
     * Can be used to check if the autoloader uses the include path to check
     * for classes.
     *
     */
    public function getUseIncludePath(): bool
    {
        return $this->useIncludePath;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false): void
    {
        spl_autoload_register(fn(string $class): ?bool => $this->loadClass($class), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister(): void
    {
        spl_autoload_unregister(fn(string $class): ?bool => $this->loadClass($class));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $class The name of the class
     *
     * @return bool|null True, if loaded
     */
    public function loadClass($class): ?bool
    {
        if ($file = $this->findFile($class)) {
            require $file;

            return true;
        }

        return null;
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $class The name of the class
     *
     * @return string|null The path, if found
     */
    public function findFile($class): ?string
    {
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $classPath = str_replace('\\', \DIRECTORY_SEPARATOR, substr($class, 0, $pos)).\DIRECTORY_SEPARATOR;
            $className = substr($class, $pos + 1);
        } else {
            // PEAR-like class name
            $classPath = null;
            $className = $class;
        }

        $classPath .= str_replace('_', \DIRECTORY_SEPARATOR, $className).'.php';

        foreach ($this->prefixes as $prefix => $dirs) {
            if ($class === strstr($class, $prefix)) {
                foreach ($dirs as $dir) {
                    if (file_exists($dir.\DIRECTORY_SEPARATOR.$classPath)) {
                        return $dir.\DIRECTORY_SEPARATOR.$classPath;
                    }
                }
            }
        }

        foreach ($this->fallbackDirs as $dir) {
            if (file_exists($dir.\DIRECTORY_SEPARATOR.$classPath)) {
                return $dir.\DIRECTORY_SEPARATOR.$classPath;
            }
        }

        if ($this->useIncludePath && $file = stream_resolve_include_path($classPath)) {
            return $file;
        }

        return null;
    }
}
