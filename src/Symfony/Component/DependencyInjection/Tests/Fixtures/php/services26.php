<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/**
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 *
 * @final since Symfony 3.3
 */
class Symfony_DI_PhpDumper_Test_EnvParameters extends Container
{
    private $parameters = [];

    private array $targetDirs = [];

    public function __construct()
    {
        $dir = __DIR__;
        for ($i = 1; $i <= 5; ++$i) {
            $this->targetDirs[$i] = $dir = \dirname($dir);
        }

        $this->parameters = $this->getDefaultParameters();

        $this->services = [];
        $this->methodMap = [
            'bar' => 'getBarService',
            'test' => 'getTestService',
        ];

        $this->aliases = [];
    }

    public function getRemovedIds(): array
    {
        return [
            \Psr\Container\ContainerInterface::class => true,
            \Symfony\Component\DependencyInjection\ContainerInterface::class => true,
        ];
    }

    public function compile(): never
    {
        throw new LogicException('You cannot compile a dumped container that was already compiled.');
    }

    public function isCompiled(): bool
    {
        return true;
    }

    public function isFrozen(): bool
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the isCompiled() method instead.', __METHOD__), E_USER_DEPRECATED);

        return true;
    }

    /**
     * Gets the public 'bar' shared service.
     *
     * @return \Symfony\Component\DependencyInjection\Tests\Fixtures\Bar
     */
    protected function getBarService()
    {
        return $this->services['bar'] = new \Symfony\Component\DependencyInjection\Tests\Fixtures\Bar($this->getEnv('QUZ'));
    }

    /**
     * Gets the public 'test' shared service.
     *
     * @return object A %env(FOO)% instance
     */
    protected function getTestService()
    {
        $class = $this->getEnv('FOO');

        return $this->services['test'] = new $class($this->getEnv('Bar'), 'foo'.$this->getEnv('string:FOO').'baz', $this->getEnv('int:Baz'));
    }

    public function getParameter($name)
    {
        $name = (string) $name;
        if (!(isset($this->parameters[$name]) || isset($this->loadedDynamicParameters[$name]) || array_key_exists($name, $this->parameters))) {
            $name = $this->normalizeParameterName($name);

            if (!(isset($this->parameters[$name]) || isset($this->loadedDynamicParameters[$name]) || array_key_exists($name, $this->parameters))) {
                throw new InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
            }
        }

        if (isset($this->loadedDynamicParameters[$name])) {
            return $this->loadedDynamicParameters[$name] ? $this->dynamicParameters[$name] : $this->getDynamicParameter($name);
        }

        return $this->parameters[$name];
    }

    public function hasParameter($name): bool
    {
        $name = (string) $name;
        $name = $this->normalizeParameterName($name);

        return isset($this->parameters[$name]) || isset($this->loadedDynamicParameters[$name]) || array_key_exists($name, $this->parameters);
    }

    public function setParameter($name, $value): never
    {
        throw new LogicException('Impossible to call set() on a frozen ParameterBag.');
    }

    public function getParameterBag()
    {
        if (null === $this->parameterBag) {
            $parameters = $this->parameters;
            foreach ($this->loadedDynamicParameters as $name => $loaded) {
                $parameters[$name] = $loaded ? $this->dynamicParameters[$name] : $this->getDynamicParameter($name);
            }

            $this->parameterBag = new FrozenParameterBag($parameters);
        }

        return $this->parameterBag;
    }

    private $loadedDynamicParameters = [
        'bar' => false,
        'baz' => false,
        'json' => false,
        'db_dsn' => false,
        'env(json_file)' => false,
    ];

    private array $dynamicParameters = [];

    /**
     * Computes a dynamic parameter.
     *
     * @param string $name The name of the dynamic parameter to load
     *
     * @return mixed The value of the dynamic parameter
     *
     * @throws InvalidArgumentException When the dynamic parameter does not exist
     */
    private function getDynamicParameter($name)
    {
        switch ($name) {
            case 'bar': $value = $this->getEnv('FOO'); break;
            case 'baz': $value = $this->getEnv('int:Baz'); break;
            case 'json': $value = $this->getEnv('json:file:json_file'); break;
            case 'db_dsn': $value = $this->getEnv('resolve:DB'); break;
            case 'env(json_file)': $value = ($this->targetDirs[1].'/array.json'); break;
            default: throw new InvalidArgumentException(sprintf('The dynamic parameter "%s" must be defined.', $name));
        }

        $this->loadedDynamicParameters[$name] = true;

        return $this->dynamicParameters[$name] = $value;
    }

    private array $normalizedParameterNames = [
        'env(foo)' => 'env(FOO)',
        'env(db)' => 'env(DB)',
    ];

    private function normalizeParameterName(string $name)
    {
        if (isset($this->normalizedParameterNames[$normalizedName = strtolower($name)]) || isset($this->parameters[$normalizedName]) || array_key_exists($normalizedName, $this->parameters)) {
            $normalizedName = isset($this->normalizedParameterNames[$normalizedName]) ? $this->normalizedParameterNames[$normalizedName] : $normalizedName;
            if ($name !== $normalizedName) {
                @trigger_error(sprintf('Parameter names will be made case sensitive in Symfony 4.0. Using "%s" instead of "%s" is deprecated since Symfony 3.4.', $name, $normalizedName), E_USER_DEPRECATED);
            }
        } else {
            $normalizedName = $this->normalizedParameterNames[$normalizedName] = $name;
        }

        return $normalizedName;
    }

    /**
     * Gets the default parameters.
     *
     * @return array An array of the default parameters
     */
    protected function getDefaultParameters(): array
    {
        return [
            'project_dir' => '/foo/bar',
            'env(FOO)' => 'foo',
            'env(DB)' => 'sqlite://%project_dir%/var/data.db',
        ];
    }
}
