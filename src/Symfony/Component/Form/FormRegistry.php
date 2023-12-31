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

use Symfony\Component\Form\Exception\ExceptionInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * The central registry of the Form component.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormRegistry implements FormRegistryInterface
{
    /**
     * Extensions.
     *
     * @var FormExtensionInterface[]
     */
    private array $extensions = [];

    /**
     * @var ResolvedFormTypeInterface[]
     */
    private array $types = [];

    /**
     * @var FormTypeGuesserInterface|false|null
     */
    private $guesser = false;

    private readonly \Symfony\Component\Form\ResolvedFormTypeFactoryInterface $resolvedTypeFactory;

    private array $checkedTypes = [];

    /**
     * @param FormExtensionInterface[]         $extensions          An array of FormExtensionInterface
     * @param ResolvedFormTypeFactoryInterface $resolvedTypeFactory The factory for resolved form types
     *
     * @throws UnexpectedTypeException if any extension does not implement FormExtensionInterface
     */
    public function __construct(array $extensions, ResolvedFormTypeFactoryInterface $resolvedTypeFactory)
    {
        foreach ($extensions as $extension) {
            if (!$extension instanceof FormExtensionInterface) {
                throw new UnexpectedTypeException($extension, \Symfony\Component\Form\FormExtensionInterface::class);
            }
        }

        $this->extensions = $extensions;
        $this->resolvedTypeFactory = $resolvedTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (!isset($this->types[$name])) {
            $type = null;

            foreach ($this->extensions as $extension) {
                if ($extension->hasType($name)) {
                    $type = $extension->getType($name);
                    break;
                }
            }

            if ($type === null) {
                // Support fully-qualified class names
                if (!class_exists($name)) {
                    throw new InvalidArgumentException(sprintf('Could not load type "%s": class does not exist.', $name));
                }

                if (!is_subclass_of($name, \Symfony\Component\Form\FormTypeInterface::class)) {
                    throw new InvalidArgumentException(sprintf('Could not load type "%s": class does not implement "Symfony\Component\Form\FormTypeInterface".', $name));
                }

                $type = new $name();
            }

            $this->types[$name] = $this->resolveType($type);
        }

        return $this->types[$name];
    }

    /**
     * Wraps a type into a ResolvedFormTypeInterface implementation and connects
     * it with its parent type.
     *
     * @param FormTypeInterface $type The type to resolve
     *
     * @return ResolvedFormTypeInterface The resolved type
     */
    private function resolveType(FormTypeInterface $type)
    {
        $typeExtensions = [];
        $parentType = $type->getParent();
        $fqcn = \get_class($type);

        if (isset($this->checkedTypes[$fqcn])) {
            $types = implode(' > ', [...array_keys($this->checkedTypes), $fqcn]);
            throw new LogicException(sprintf('Circular reference detected for form type "%s" (%s).', $fqcn, $types));
        }

        $this->checkedTypes[$fqcn] = true;

        try {
            foreach ($this->extensions as $extension) {
                $typeExtensions = array_merge(
                    $typeExtensions,
                    $extension->getTypeExtensions($fqcn)
                );
            }

            return $this->resolvedTypeFactory->createResolvedType(
                $type,
                $typeExtensions,
                $parentType ? $this->getType($parentType) : null
            );
        } finally {
            unset($this->checkedTypes[$fqcn]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name): bool
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getType($name);
        } catch (ExceptionInterface $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeGuesser()
    {
        if (false === $this->guesser) {
            $guessers = [];

            foreach ($this->extensions as $extension) {
                $guesser = $extension->getTypeGuesser();

                if ($guesser !== null) {
                    $guessers[] = $guesser;
                }
            }

            $this->guesser = $guessers === [] ? null : new FormTypeGuesserChain($guessers);
        }

        return $this->guesser;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
