<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class ProjectUrlMatcher extends Symfony\Component\Routing\Matcher\UrlMatcher
{
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($rawPathinfo)
    {
        $allow = [];
        $pathinfo = rawurldecode($rawPathinfo);
        $context = $this->context;
        $this->request ?: $this->createRequest($pathinfo);
        $requestMethod = $context->getMethod();
        $canonicalMethod = $requestMethod;

        if ('HEAD' === $requestMethod) {
            $canonicalMethod = 'GET';
        }

        if (0 === strpos($pathinfo, '/rootprefix')) {
            // static
            if ('/rootprefix/test' === $pathinfo) {
                return ['_route' => 'static'];
            }

            // dynamic
            if (preg_match('#^/rootprefix/(?P<var>[^/]++)$#sD', $pathinfo, $matches)) {
                return $this->mergeDefaults(array_replace($matches, ['_route' => 'dynamic']), array ());
            }

        }

        // with-condition
        if ('/with-condition' === $pathinfo && ($context->getMethod() == "GET")) {
            return ['_route' => 'with-condition'];
        }

        if ('/' === $pathinfo && !$allow) {
            throw new Symfony\Component\Routing\Exception\NoConfigurationException();
        }

        throw [] !== $allow ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
