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

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\DoctrineProvider;

/**
 * Warms up annotation caches for classes found in composer's autoload class map
 * and declared in DI bundle extensions using the addAnnotatedClassesToCache method.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AnnotationsCacheWarmer extends AbstractPhpFileCacheWarmer
{
    private $annotationReader;

    private $excludeRegexp;

    private $debug;

    /**
     * @param string                 $phpArrayFile The PHP file where annotations are cached
     * @param CacheItemPoolInterface $fallbackPool The pool where runtime-discovered annotations are cached
     * @param bool                   $debug        Run in debug mode
     */
    public function __construct(Reader $annotationReader, $phpArrayFile, CacheItemPoolInterface $fallbackPool, $excludeRegexp = null, $debug = false)
    {
        parent::__construct($phpArrayFile, $fallbackPool);
        $this->annotationReader = $annotationReader;
        $this->excludeRegexp = $excludeRegexp;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    protected function doWarmUp($cacheDir, ArrayAdapter $arrayAdapter): bool
    {
        $annotatedClassPatterns = $cacheDir.'/annotations.map';

        if (!is_file($annotatedClassPatterns)) {
            return true;
        }

        $annotatedClasses = include $annotatedClassPatterns;
        $reader = new CachedReader($this->annotationReader, new DoctrineProvider($arrayAdapter), $this->debug);

        foreach ($annotatedClasses as $class) {
            if (null !== $this->excludeRegexp && preg_match($this->excludeRegexp, $class)) {
                continue;
            }

            try {
                $this->readAllComponents($reader, $class);
            } catch (\Exception $e) {
                $this->ignoreAutoloadException($class, $e);
            }
        }

        return true;
    }

    private function readAllComponents(Reader $reader, $class): void
    {
        $reflectionClass = new \ReflectionClass($class);

        try {
            $reader->getClassAnnotations($reflectionClass);
        } catch (AnnotationException $annotationException) {
            /*
             * Ignore any AnnotationException to not break the cache warming process if an Annotation is badly
             * configured or could not be found / read / etc.
             *
             * In particular cases, an Annotation in your code can be used and defined only for a specific
             * environment but is always added to the annotations.map file by some Symfony default behaviors,
             * and you always end up with a not found Annotation.
             */
        }

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            try {
                $reader->getMethodAnnotations($reflectionMethod);
            } catch (AnnotationException $annotationException) {
            }
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            try {
                $reader->getPropertyAnnotations($reflectionProperty);
            } catch (AnnotationException $annotationException) {
            }
        }
    }
}
