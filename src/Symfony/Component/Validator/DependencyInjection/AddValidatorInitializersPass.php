<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class AddValidatorInitializersPass implements CompilerPassInterface
{
    private readonly string $builderService;

    private readonly string $initializerTag;

    public function __construct(string $builderService = 'validator.builder', string $initializerTag = 'validator.initializer')
    {
        $this->builderService = $builderService;
        $this->initializerTag = $initializerTag;
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition($this->builderService)) {
            return;
        }

        $initializers = [];
        foreach (array_keys($container->findTaggedServiceIds($this->initializerTag, true)) as $id) {
            $initializers[] = new Reference($id);
        }

        $container->getDefinition($this->builderService)->addMethodCall('addObjectInitializers', [$initializers]);
    }
}
