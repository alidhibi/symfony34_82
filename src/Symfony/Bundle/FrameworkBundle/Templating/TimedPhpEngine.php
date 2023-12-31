<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Templating;

use Psr\Container\ContainerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;

/**
 * Times the time spent to render a template.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TimedPhpEngine extends PhpEngine
{
    protected \Symfony\Component\Stopwatch\Stopwatch $stopwatch;

    public function __construct(TemplateNameParserInterface $parser, ContainerInterface $container, LoaderInterface $loader, Stopwatch $stopwatch, GlobalVariables $globals = null)
    {
        parent::__construct($parser, $container, $loader, $globals);

        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = [])
    {
        $e = $this->stopwatch->start(sprintf('template.php (%s)', $name), 'template');

        $ret = null;

        $e->stop();

        return $ret;
    }
}
