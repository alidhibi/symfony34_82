<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Matcher;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class RedirectableUrlMatcher extends UrlMatcher implements RedirectableUrlMatcherInterface
{
    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        try {
            $parameters = parent::match($pathinfo);
        } catch (ResourceNotFoundException $resourceNotFoundException) {
            if ('/' === substr($pathinfo, -1) || !\in_array($this->context->getMethod(), ['HEAD', 'GET'])) {
                throw $resourceNotFoundException;
            }

            try {
                $parameters = parent::match($pathinfo.'/');

                return array_replace($parameters, $this->redirect($pathinfo.'/', isset($parameters['_route']) ? $parameters['_route'] : null));
            } catch (ResourceNotFoundException $e2) {
                throw $resourceNotFoundException;
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleRouteRequirements($pathinfo, $name, Route $route)
    {
        // expression condition
        if ($route->getCondition() && !$this->getExpressionLanguage()->evaluate($route->getCondition(), ['context' => $this->context, 'request' => $this->request ?: $this->createRequest($pathinfo)])) {
            return [self::REQUIREMENT_MISMATCH, null];
        }

        // check HTTP scheme requirement
        $scheme = $this->context->getScheme();
        $schemes = $route->getSchemes();
        if ($schemes && !$route->hasScheme($scheme)) {
            return [self::ROUTE_MATCH, $this->redirect($pathinfo, $name, current($schemes))];
        }

        return [self::REQUIREMENT_MATCH, null];
    }
}
