<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Extension\DataCollector\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\DataCollector\EventListener\DataCollectorListener;
use Symfony\Component\Form\Extension\DataCollector\FormDataCollectorInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Type extension for collecting data of a form with this type.
 *
 * @author Robert Schönthal <robert.schoenthal@gmail.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DataCollectorTypeExtension extends AbstractTypeExtension
{
    private readonly \Symfony\Component\Form\Extension\DataCollector\EventListener\DataCollectorListener $listener;

    public function __construct(FormDataCollectorInterface $dataCollector)
    {
        $this->listener = new DataCollectorListener($dataCollector);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this->listener);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\FormType::class;
    }
}
