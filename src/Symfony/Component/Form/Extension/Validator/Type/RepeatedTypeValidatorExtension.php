<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Extension\Validator\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RepeatedTypeValidatorExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Map errors to the first field
        $errorMapping = static fn(Options $options): array => ['.' => $options['first_name']];

        $resolver->setDefaults([
            'error_mapping' => $errorMapping,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\RepeatedType::class;
    }
}
