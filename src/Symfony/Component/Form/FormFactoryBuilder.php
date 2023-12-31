<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form;

use Symfony\Component\Form\Extension\Core\CoreExtension;

/**
 * The default implementation of FormFactoryBuilderInterface.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormFactoryBuilder implements FormFactoryBuilderInterface
{
    private $forceCoreExtension;

    private ?\Symfony\Component\Form\ResolvedFormTypeFactoryInterface $resolvedTypeFactory = null;

    /**
     * @var FormExtensionInterface[]
     */
    private $extensions = [];

    /**
     * @var FormTypeInterface[]
     */
    private array $types = [];

    /**
     * @var FormTypeExtensionInterface[]
     */
    private array $typeExtensions = [];

    /**
     * @var FormTypeGuesserInterface[]
     */
    private $typeGuessers = [];

    /**
     * @param bool $forceCoreExtension
     */
    public function __construct($forceCoreExtension = false)
    {
        $this->forceCoreExtension = $forceCoreExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setResolvedTypeFactory(ResolvedFormTypeFactoryInterface $resolvedTypeFactory): static
    {
        $this->resolvedTypeFactory = $resolvedTypeFactory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(FormExtensionInterface $extension): static
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtensions(array $extensions): static
    {
        $this->extensions = [...$this->extensions, ...$extensions];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addType(FormTypeInterface $type): static
    {
        $this->types[] = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addTypes(array $types): static
    {
        foreach ($types as $type) {
            $this->types[] = $type;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addTypeExtension(FormTypeExtensionInterface $typeExtension): static
    {
        $this->typeExtensions[$typeExtension->getExtendedType()][] = $typeExtension;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addTypeExtensions(array $typeExtensions): static
    {
        foreach ($typeExtensions as $typeExtension) {
            $this->typeExtensions[$typeExtension->getExtendedType()][] = $typeExtension;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addTypeGuesser(FormTypeGuesserInterface $typeGuesser): static
    {
        $this->typeGuessers[] = $typeGuesser;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addTypeGuessers(array $typeGuessers): static
    {
        $this->typeGuessers = [...$this->typeGuessers, ...$typeGuessers];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormFactory(): \Symfony\Component\Form\FormFactory
    {
        $extensions = $this->extensions;

        if ($this->forceCoreExtension) {
            $hasCoreExtension = false;

            foreach ($extensions as $extension) {
                if ($extension instanceof CoreExtension) {
                    $hasCoreExtension = true;
                    break;
                }
            }

            if (!$hasCoreExtension) {
                array_unshift($extensions, new CoreExtension());
            }
        }

        if ($this->types !== [] || $this->typeExtensions !== [] || $this->typeGuessers !== []) {
            if (\count($this->typeGuessers) > 1) {
                $typeGuesser = new FormTypeGuesserChain($this->typeGuessers);
            } else {
                $typeGuesser = isset($this->typeGuessers[0]) ? $this->typeGuessers[0] : null;
            }

            $extensions[] = new PreloadedExtension($this->types, $this->typeExtensions, $typeGuesser);
        }

        $registry = new FormRegistry($extensions, $this->resolvedTypeFactory ?: new ResolvedFormTypeFactory());

        return new FormFactory($registry);
    }
}
