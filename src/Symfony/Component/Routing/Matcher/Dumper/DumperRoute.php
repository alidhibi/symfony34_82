<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Matcher\Dumper;

use Symfony\Component\Routing\Route;

/**
 * Container for a Route.
 *
 * @author Arnaud Le Blanc <arnaud.lb@gmail.com>
 *
 * @internal
 */
class DumperRoute
{
    private $name;

    private readonly \Symfony\Component\Routing\Route $route;

    /**
     * @param string $name  The route name
     * @param Route  $route The route
     */
    public function __construct($name, Route $route)
    {
        $this->name = $name;
        $this->route = $route;
    }

    /**
     * Returns the route name.
     *
     * @return string The route name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the route.
     *
     * @return Route The route
     */
    public function getRoute(): \Symfony\Component\Routing\Route
    {
        return $this->route;
    }
}
