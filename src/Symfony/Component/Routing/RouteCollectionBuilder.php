<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing;

use Symfony\Component\Config\Exception\FileLoaderLoadException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Helps add and import routes into a RouteCollection.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
class RouteCollectionBuilder
{
    /**
     * @var Route[]|RouteCollectionBuilder[]
     */
    private $routes = [];

    private ?\Symfony\Component\Config\Loader\LoaderInterface $loader = null;

    private array $defaults = [];

    private ?string $prefix = null;

    private $host;

    private $condition;

    private array $requirements = [];

    private array $options = [];

    private $schemes;

    private $methods;

    private array $resources = [];

    public function __construct(LoaderInterface $loader = null)
    {
        $this->loader = $loader;
    }

    /**
     * Import an external routing resource and returns the RouteCollectionBuilder.
     *
     *     $routes->import('blog.yml', '/blog');
     *
     * @param mixed       $resource
     * @param string|null $prefix
     * @param string      $type
     *
     * @return self
     *
     * @throws FileLoaderLoadException
     */
    public function import(\Symfony\Component\Config\Resource\ResourceInterface $resource, $prefix = '/', $type = null)
    {
        $collections = $this->load($resource, $type);

        // create a builder from the RouteCollection
        $builder = $this->createBuilder();

        foreach ($collections as $collection) {
            if (null === $collection) {
                continue;
            }

            foreach ($collection->all() as $name => $route) {
                $builder->addRoute($route, $name);
            }

            foreach ($collection->getResources() as $resource) {
                $builder->addResource($resource);
            }
        }

        // mount into this builder
        $this->mount($prefix, $builder);

        return $builder;
    }

    /**
     * Adds a route and returns it for future modification.
     *
     * @param string      $path       The route path
     * @param string      $controller The route's controller
     * @param string|null $name       The name to give this route
     *
     */
    public function add($path, $controller, $name = null): \Symfony\Component\Routing\Route
    {
        $route = new Route($path);
        $route->setDefault('_controller', $controller);
        $this->addRoute($route, $name);

        return $route;
    }

    /**
     * Returns a RouteCollectionBuilder that can be configured and then added with mount().
     *
     */
    public function createBuilder(): self
    {
        return new self($this->loader);
    }

    /**
     * Add a RouteCollectionBuilder.
     *
     * @param string                 $prefix
     */
    public function mount($prefix, self $builder): void
    {
        $builder->prefix = trim(trim($prefix), '/');
        $this->routes[] = $builder;
    }

    /**
     * Adds a Route object to the builder.
     *
     * @param string|null $name
     *
     * @return $this
     */
    public function addRoute(Route $route, $name = null): static
    {
        if (null === $name) {
            // used as a flag to know which routes will need a name later
            $name = '_unnamed_route_'.spl_object_hash($route);
        }

        $this->routes[$name] = $route;

        return $this;
    }

    /**
     * Sets the host on all embedded routes (unless already set).
     *
     * @param string $pattern
     *
     * @return $this
     */
    public function setHost($pattern): static
    {
        $this->host = $pattern;

        return $this;
    }

    /**
     * Sets a condition on all embedded routes (unless already set).
     *
     * @param string $condition
     *
     * @return $this
     */
    public function setCondition($condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Sets a default value that will be added to all embedded routes (unless that
     * default value is already set).
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setDefault($key, $value): static
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * Sets a requirement that will be added to all embedded routes (unless that
     * requirement is already set).
     *
     * @param string $key
     * @param mixed  $regex
     *
     * @return $this
     */
    public function setRequirement($key, $regex): static
    {
        $this->requirements[$key] = $regex;

        return $this;
    }

    /**
     * Sets an option that will be added to all embedded routes (unless that
     * option is already set).
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setOption($key, $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Sets the schemes on all embedded routes (unless already set).
     *
     * @param array|string $schemes
     *
     * @return $this
     */
    public function setSchemes($schemes): static
    {
        $this->schemes = $schemes;

        return $this;
    }

    /**
     * Sets the methods on all embedded routes (unless already set).
     *
     * @param array|string $methods
     *
     * @return $this
     */
    public function setMethods($methods): static
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Adds a resource for this collection.
     *
     * @return $this
     */
    private function addResource(ResourceInterface $resource): static
    {
        $this->resources[] = $resource;

        return $this;
    }

    /**
     * Creates the final RouteCollection and returns it.
     *
     */
    public function build(): \Symfony\Component\Routing\RouteCollection
    {
        $routeCollection = new RouteCollection();

        foreach ($this->routes as $name => $route) {
            if ($route instanceof Route) {
                $route->setDefaults(array_merge($this->defaults, $route->getDefaults()));
                $route->setOptions(array_merge($this->options, $route->getOptions()));

                foreach ($this->requirements as $key => $val) {
                    if (!$route->hasRequirement($key)) {
                        $route->setRequirement($key, $val);
                    }
                }

                if (null !== $this->prefix) {
                    $route->setPath('/'.$this->prefix.$route->getPath());
                }

                if ($route->getHost() === '' || $route->getHost() === '0') {
                    $route->setHost($this->host);
                }

                if ($route->getCondition() === '' || $route->getCondition() === '0') {
                    $route->setCondition($this->condition);
                }

                if (!$route->getSchemes()) {
                    $route->setSchemes($this->schemes);
                }

                if (!$route->getMethods()) {
                    $route->setMethods($this->methods);
                }

                // auto-generate the route name if it's been marked
                if ('_unnamed_route_' === substr($name, 0, 15)) {
                    $name = $this->generateRouteName($route);
                }

                $routeCollection->add($name, $route);
            } else {
                /* @var self $route */
                $subCollection = $route->build();
                $subCollection->addPrefix($this->prefix);

                $routeCollection->addCollection($subCollection);
            }
        }

        foreach ($this->resources as $resource) {
            $routeCollection->addResource($resource);
        }

        return $routeCollection;
    }

    /**
     * Generates a route name based on details of this route.
     *
     * @return string
     */
    private function generateRouteName(Route $route): array|string|null
    {
        $methods = implode('_', $route->getMethods()).'_';

        $routeName = $methods.$route->getPath();
        $routeName = str_replace(['/', ':', '|', '-'], '_', $routeName);
        $routeName = preg_replace('/[^a-z0-9A-Z_.]+/', '', $routeName);

        // Collapse consecutive underscores down into a single underscore.
        $routeName = preg_replace('/_+/', '_', $routeName);

        return $routeName;
    }

    /**
     * Finds a loader able to load an imported resource and loads it.
     *
     * @param mixed       $resource A resource
     * @param string|null $type     The resource type or null if unknown
     *
     * @return RouteCollection[]
     *
     * @throws FileLoaderLoadException If no loader is found
     */
    private function load(\Symfony\Component\Config\Resource\ResourceInterface $resource, $type = null)
    {
        if (!$this->loader instanceof \Symfony\Component\Config\Loader\LoaderInterface) {
            throw new \BadMethodCallException('Cannot import other routing resources: you must pass a LoaderInterface when constructing RouteCollectionBuilder.');
        }

        if ($this->loader->supports($resource, $type)) {
            $collections = $this->loader->load($resource, $type);

            return \is_array($collections) ? $collections : [$collections];
        }

        if (null === $resolver = $this->loader->getResolver()) {
            throw new FileLoaderLoadException($resource, null, null, null, $type);
        }

        if (false === $loader = $resolver->resolve($resource, $type)) {
            throw new FileLoaderLoadException($resource, null, null, null, $type);
        }

        $collections = $loader->load($resource, $type);

        return \is_array($collections) ? $collections : [$collections];
    }
}
