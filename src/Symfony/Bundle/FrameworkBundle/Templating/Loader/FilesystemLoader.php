<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Templating\Loader;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\Storage\FileStorage;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * FilesystemLoader is a loader that read templates from the filesystem.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FilesystemLoader implements LoaderInterface
{
    protected \Symfony\Component\Config\FileLocatorInterface $locator;

    public function __construct(FileLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function load(TemplateReferenceInterface $template): bool|\Symfony\Component\Templating\Storage\FileStorage
    {
        try {
            $file = $this->locator->locate($template);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return false;
        }

        return new FileStorage($file);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh(TemplateReferenceInterface $template, $time): bool
    {
        if (false === $storage = $this->load($template)) {
            return false;
        }

        if (!is_readable((string) $storage)) {
            return false;
        }

        return filemtime((string) $storage) < $time;
    }
}
