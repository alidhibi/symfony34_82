<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\ChoiceList\Factory;

use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceListView;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Adds property path support to a choice list factory.
 *
 * Pass the decorated factory to the constructor:
 *
 *     $decorator = new PropertyAccessDecorator($factory);
 *
 * You can now pass property paths for generating choice values, labels, view
 * indices, HTML attributes and for determining the preferred choices and the
 * choice groups:
 *
 *     // extract values from the $value property
 *     $list = $createListFromChoices($objects, 'value');
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PropertyAccessDecorator implements ChoiceListFactoryInterface
{
    private readonly \Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface $decoratedFactory;

    private $propertyAccessor;

    public function __construct(ChoiceListFactoryInterface $decoratedFactory, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->decoratedFactory = $decoratedFactory;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * Returns the decorated factory.
     *
     * @return ChoiceListFactoryInterface The decorated factory
     */
    public function getDecoratedFactory(): \Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface
    {
        return $this->decoratedFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable                          $choices The choices
     * @param callable|string|PropertyPath|null $value   The callable or path for
     *                                                   generating the choice values
     *
     * @return ChoiceListInterface The choice list
     */
    public function createListFromChoices($choices, $value = null)
    {
        if (\is_string($value) && !\is_callable($value)) {
            $value = new PropertyPath($value);
        } elseif (\is_string($value) && \is_callable($value)) {
            @trigger_error('Passing callable strings is deprecated since Symfony 3.1 and PropertyAccessDecorator will treat them as property paths in 4.0. You should use a "\Closure" instead.', \E_USER_DEPRECATED);
        }

        if ($value instanceof PropertyPath) {
            $accessor = $this->propertyAccessor;
            $value = static fn($choice) => \is_object($choice) || \is_array($choice) ? $accessor->getValue($choice, $value) : null;
        }

        return $this->decoratedFactory->createListFromChoices($choices, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param ChoiceLoaderInterface             $loader The choice loader
     * @param callable|string|PropertyPath|null $value  The callable or path for
     *                                                  generating the choice values
     *
     * @return ChoiceListInterface The choice list
     */
    public function createListFromLoader(ChoiceLoaderInterface $loader, $value = null)
    {
        if (\is_string($value) && !\is_callable($value)) {
            $value = new PropertyPath($value);
        } elseif (\is_string($value) && \is_callable($value)) {
            @trigger_error('Passing callable strings is deprecated since Symfony 3.1 and PropertyAccessDecorator will treat them as property paths in 4.0. You should use a "\Closure" instead.', \E_USER_DEPRECATED);
        }

        if ($value instanceof PropertyPath) {
            $accessor = $this->propertyAccessor;
            $value = static fn($choice) => \is_object($choice) || \is_array($choice) ? $accessor->getValue($choice, $value) : null;
        }

        return $this->decoratedFactory->createListFromLoader($loader, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param ChoiceListInterface                     $list             The choice list
     * @param array|callable|string|PropertyPath|null $preferredChoices The preferred choices
     * @param callable|string|PropertyPath|null       $label            The callable or path generating the choice labels
     * @param callable|string|PropertyPath|null       $index            The callable or path generating the view indices
     * @param callable|string|PropertyPath|null       $groupBy          The callable or path generating the group names
     * @param array|callable|string|PropertyPath|null $attr             The callable or path generating the HTML attributes
     *
     * @return ChoiceListView The choice list view
     */
    public function createView(ChoiceListInterface $list, $preferredChoices = null, $label = null, $index = null, $groupBy = null, $attr = null)
    {
        $accessor = $this->propertyAccessor;

        if (\is_string($label) && !\is_callable($label)) {
            $label = new PropertyPath($label);
        } elseif (\is_string($label) && \is_callable($label)) {
            @trigger_error('Passing callable strings is deprecated since Symfony 3.1 and PropertyAccessDecorator will treat them as property paths in 4.0. You should use a "\Closure" instead.', \E_USER_DEPRECATED);
        }

        if ($label instanceof PropertyPath) {
            $label = static fn($choice) => $accessor->getValue($choice, $label);
        }

        if (\is_string($preferredChoices) && !\is_callable($preferredChoices)) {
            $preferredChoices = new PropertyPath($preferredChoices);
        } elseif (\is_string($preferredChoices) && \is_callable($preferredChoices)) {
            @trigger_error('Passing callable strings is deprecated since Symfony 3.1 and PropertyAccessDecorator will treat them as property paths in 4.0. You should use a "\Closure" instead.', \E_USER_DEPRECATED);
        }

        if ($preferredChoices instanceof PropertyPath) {
            $preferredChoices = static function ($choice) use ($accessor, $preferredChoices) {
                try {
                    return $accessor->getValue($choice, $preferredChoices);
                } catch (UnexpectedTypeException $unexpectedTypeException) {
                    // Assume not preferred if not readable
                    return false;
                }
            };
        }

        if (\is_string($index) && !\is_callable($index)) {
            $index = new PropertyPath($index);
        } elseif (\is_string($index) && \is_callable($index)) {
            @trigger_error('Passing callable strings is deprecated since Symfony 3.1 and PropertyAccessDecorator will treat them as property paths in 4.0. You should use a "\Closure" instead.', \E_USER_DEPRECATED);
        }

        if ($index instanceof PropertyPath) {
            $index = static fn($choice) => $accessor->getValue($choice, $index);
        }

        if (\is_string($groupBy) && !\is_callable($groupBy)) {
            $groupBy = new PropertyPath($groupBy);
        } elseif (\is_string($groupBy) && \is_callable($groupBy)) {
            @trigger_error('Passing callable strings is deprecated since Symfony 3.1 and PropertyAccessDecorator will treat them as property paths in 4.0. You should use a "\Closure" instead.', \E_USER_DEPRECATED);
        }

        if ($groupBy instanceof PropertyPath) {
            $groupBy = static function ($choice) use ($accessor, $groupBy) {
                try {
                    return $accessor->getValue($choice, $groupBy);
                } catch (UnexpectedTypeException $unexpectedTypeException) {
                    // Don't group if path is not readable
                    return null;
                }
            };
        }

        if (\is_string($attr) && !\is_callable($attr)) {
            $attr = new PropertyPath($attr);
        } elseif (\is_string($attr) && \is_callable($attr)) {
            @trigger_error('Passing callable strings is deprecated since Symfony 3.1 and PropertyAccessDecorator will treat them as property paths in 4.0. You should use a "\Closure" instead.', \E_USER_DEPRECATED);
        }

        if ($attr instanceof PropertyPath) {
            $attr = static fn($choice) => $accessor->getValue($choice, $attr);
        }

        return $this->decoratedFactory->createView($list, $preferredChoices, $label, $index, $groupBy, $attr);
    }
}
