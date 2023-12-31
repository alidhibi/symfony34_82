<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\TwigBundle\DependencyInjection\Configurator;

use Symfony\Bridge\Twig\UndefinedCallableHandler;
use Twig\Environment;

// BC/FC with namespaced Twig
class_exists('Twig\Environment');

/**
 * Twig environment configurator.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class EnvironmentConfigurator
{
    private $dateFormat;

    private $intervalFormat;

    private $timezone;

    private $decimals;

    private $decimalPoint;

    private $thousandsSeparator;

    public function __construct($dateFormat, $intervalFormat, $timezone, $decimals, $decimalPoint, $thousandsSeparator)
    {
        $this->dateFormat = $dateFormat;
        $this->intervalFormat = $intervalFormat;
        $this->timezone = $timezone;
        $this->decimals = $decimals;
        $this->decimalPoint = $decimalPoint;
        $this->thousandsSeparator = $thousandsSeparator;
    }

    public function configure(Environment $environment): void
    {
        $environment->getExtension('Twig\Extension\CoreExtension')->setDateFormat($this->dateFormat, $this->intervalFormat);

        if (null !== $this->timezone) {
            $environment->getExtension('Twig\Extension\CoreExtension')->setTimezone($this->timezone);
        }

        $environment->getExtension('Twig\Extension\CoreExtension')->setNumberFormat($this->decimals, $this->decimalPoint, $this->thousandsSeparator);

        // wrap UndefinedCallableHandler in closures for lazy-autoloading
        $environment->registerUndefinedFilterCallback(static fn($name) => UndefinedCallableHandler::onUndefinedFilter($name));
        $environment->registerUndefinedFunctionCallback(static fn($name) => UndefinedCallableHandler::onUndefinedFunction($name));
    }
}
