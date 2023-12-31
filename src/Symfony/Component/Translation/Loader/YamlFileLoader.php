<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Translation\Loader;

use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\LogicException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * YamlFileLoader loads translations from Yaml files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class YamlFileLoader extends FileLoader
{
    private ?\Symfony\Component\Yaml\Parser $yamlParser = null;

    /**
     * {@inheritdoc}
     */
    protected function loadResource($resource)
    {
        if (!$this->yamlParser instanceof \Symfony\Component\Yaml\Parser) {
            if (!class_exists(\Symfony\Component\Yaml\Parser::class)) {
                throw new LogicException('Loading translations from the YAML format requires the Symfony Yaml component.');
            }

            $this->yamlParser = new YamlParser();
        }

        set_error_handler(static function ($level, $message, $script, $line) use ($resource, &$prevErrorHandler) : bool {
            $message = \E_USER_DEPRECATED === $level ? preg_replace('/ on line \d+/', ' in "'.$resource.'"$0', $message) : $message;
            return $prevErrorHandler ? $prevErrorHandler($level, $message, $script, $line) : false;
        });

        try {
            $messages = $this->yamlParser->parseFile($resource);
        } catch (ParseException $parseException) {
            throw new InvalidResourceException(sprintf('The file "%s" does not contain valid YAML: ', $resource).$parseException->getMessage(), 0, $parseException);
        } finally {
            restore_error_handler();
        }

        if (null !== $messages && !\is_array($messages)) {
            throw new InvalidResourceException(sprintf('Unable to load file "%s".', $resource));
        }

        return $messages ?: [];
    }
}
