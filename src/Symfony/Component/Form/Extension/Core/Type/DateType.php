<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Extension\Core\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToTimestampTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateType extends AbstractType
{
    final const DEFAULT_FORMAT = \IntlDateFormatter::MEDIUM;

    final const HTML5_FORMAT = 'yyyy-MM-dd';

    private static array $acceptedFormats = [
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    ];

    private static array $widgets = [
        'text' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
        'choice' => \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateFormat = \is_int($options['format']) ? $options['format'] : self::DEFAULT_FORMAT;
        $timeFormat = \IntlDateFormatter::NONE;
        $calendar = \IntlDateFormatter::GREGORIAN;
        $pattern = \is_string($options['format']) ? $options['format'] : null;

        if (!\in_array($dateFormat, self::$acceptedFormats, true)) {
            throw new InvalidOptionsException('The "format" option must be one of the IntlDateFormatter constants (FULL, LONG, MEDIUM, SHORT) or a string representing a custom format.');
        }

        if ('single_text' === $options['widget']) {
            if (null !== $pattern && false === strpos($pattern, 'y') && false === strpos($pattern, 'M') && false === strpos($pattern, 'd')) {
                throw new InvalidOptionsException(sprintf('The "format" option should contain the letters "y", "M" or "d". Its current value is "%s".', $pattern));
            }

            $builder->addViewTransformer(new DateTimeToLocalizedStringTransformer(
                $options['model_timezone'],
                $options['view_timezone'],
                $dateFormat,
                $timeFormat,
                $calendar,
                $pattern
            ));
        } else {
            if (null !== $pattern && (false === strpos($pattern, 'y') || false === strpos($pattern, 'M') || false === strpos($pattern, 'd'))) {
                throw new InvalidOptionsException(sprintf('The "format" option should contain the letters "y", "M" and "d". Its current value is "%s".', $pattern));
            }
            $yearOptions = [
                'error_bubbling' => true,
                'empty_data' => '',
            ];
            $monthOptions = [
                'error_bubbling' => true,
                'empty_data' => '',
            ];
            $dayOptions = [
                'error_bubbling' => true,
                'empty_data' => '',
            ];
            // when the form is compound the entries of the array are ignored in favor of children data
            // so we need to handle the cascade setting here
            $emptyData = $builder->getEmptyData() ?: [];

            if ($emptyData instanceof \Closure) {
                $lazyEmptyData = static fn($option): \Closure => static function (FormInterface $form) use ($emptyData, $option) {
                    $emptyData = $emptyData($form->getParent());

                    return isset($emptyData[$option]) ? $emptyData[$option] : '';
                };

                $yearOptions['empty_data'] = $lazyEmptyData('year');
                $monthOptions['empty_data'] = $lazyEmptyData('month');
                $dayOptions['empty_data'] = $lazyEmptyData('day');
            } else {
                if (isset($emptyData['year'])) {
                    $yearOptions['empty_data'] = $emptyData['year'];
                }

                if (isset($emptyData['month'])) {
                    $monthOptions['empty_data'] = $emptyData['month'];
                }

                if (isset($emptyData['day'])) {
                    $dayOptions['empty_data'] = $emptyData['day'];
                }
            }

            if (isset($options['invalid_message'])) {
                $dayOptions['invalid_message'] = $options['invalid_message'];
                $monthOptions['invalid_message'] = $options['invalid_message'];
                $yearOptions['invalid_message'] = $options['invalid_message'];
            }

            if (isset($options['invalid_message_parameters'])) {
                $dayOptions['invalid_message_parameters'] = $options['invalid_message_parameters'];
                $monthOptions['invalid_message_parameters'] = $options['invalid_message_parameters'];
                $yearOptions['invalid_message_parameters'] = $options['invalid_message_parameters'];
            }

            $formatter = new \IntlDateFormatter(
                \Locale::getDefault(),
                $dateFormat,
                $timeFormat,
                // see https://bugs.php.net/66323
                class_exists('IntlTimeZone', false) ? \IntlTimeZone::createDefault() : null,
                $calendar,
                $pattern
            );

            // new \IntlDateFormatter may return null instead of false in case of failure, see https://bugs.php.net/66323
            if (!$formatter) {
                throw new InvalidOptionsException(intl_get_error_message(), intl_get_error_code());
            }

            $formatter->setLenient(false);

            if ('choice' === $options['widget']) {
                // Only pass a subset of the options to children
                $yearOptions['choices'] = $this->formatTimestamps($formatter, '/y+/', $this->listYears($options['years']));
                $yearOptions['placeholder'] = $options['placeholder']['year'];
                $yearOptions['choice_translation_domain'] = $options['choice_translation_domain']['year'];
                $monthOptions['choices'] = $this->formatTimestamps($formatter, '/[M|L]+/', $this->listMonths($options['months']));
                $monthOptions['placeholder'] = $options['placeholder']['month'];
                $monthOptions['choice_translation_domain'] = $options['choice_translation_domain']['month'];
                $dayOptions['choices'] = $this->formatTimestamps($formatter, '/d+/', $this->listDays($options['days']));
                $dayOptions['placeholder'] = $options['placeholder']['day'];
                $dayOptions['choice_translation_domain'] = $options['choice_translation_domain']['day'];
            }

            // Append generic carry-along options
            foreach (['required', 'translation_domain'] as $passOpt) {
                $yearOptions[$passOpt] = $monthOptions[$passOpt] = $dayOptions[$passOpt] = $options[$passOpt];
            }

            $builder
                ->add('year', self::$widgets[$options['widget']], $yearOptions)
                ->add('month', self::$widgets[$options['widget']], $monthOptions)
                ->add('day', self::$widgets[$options['widget']], $dayOptions)
                ->addViewTransformer(new DateTimeToArrayTransformer(
                    $options['model_timezone'], $options['view_timezone'], ['year', 'month', 'day']
                ))
                ->setAttribute('formatter', $formatter)
            ;
        }

        if ('string' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToStringTransformer($options['model_timezone'], $options['model_timezone'], 'Y-m-d')
            ));
        } elseif ('timestamp' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToTimestampTransformer($options['model_timezone'], $options['model_timezone'])
            ));
        } elseif ('array' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToArrayTransformer($options['model_timezone'], $options['model_timezone'], ['year', 'month', 'day'])
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['widget'] = $options['widget'];

        // Change the input to a HTML5 date input if
        //  * the widget is set to "single_text"
        //  * the format matches the one expected by HTML5
        //  * the html5 is set to true
        if ($options['html5'] && 'single_text' === $options['widget'] && self::HTML5_FORMAT === $options['format']) {
            $view->vars['type'] = 'date';
        }

        if ($form->getConfig()->hasAttribute('formatter')) {
            $pattern = $form->getConfig()->getAttribute('formatter')->getPattern();

            // remove special characters unless the format was explicitly specified
            if (!\is_string($options['format'])) {
                // remove quoted strings first
                $pattern = preg_replace("/'[^']+'/", '', $pattern);

                // remove remaining special chars
                $pattern = preg_replace('/[^yMd]+/', '', $pattern);
            }

            // set right order with respect to locale (e.g.: de_DE=dd.MM.yy; en_US=M/d/yy)
            // lookup various formats at http://userguide.icu-project.org/formatparse/datetime
            if (preg_match('/^([yMd]+)[^yMd]*([yMd]+)[^yMd]*([yMd]+)$/', $pattern)) {
                $pattern = preg_replace(['/y+/', '/M+/', '/d+/'], ['{{ year }}', '{{ month }}', '{{ day }}'], $pattern);
            } else {
                // default fallback
                $pattern = '{{ year }}{{ month }}{{ day }}';
            }

            $view->vars['date_pattern'] = $pattern;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $compound = static fn(Options $options): bool => 'single_text' !== $options['widget'];

        $placeholderDefault = static fn(Options $options): ?string => $options['required'] ? null : '';

        $placeholderNormalizer = static function (Options $options, $placeholder) use ($placeholderDefault) : array {
            if (\is_array($placeholder)) {
                $default = $placeholderDefault($options);

                return ['year' => $default, 'month' => $default, 'day' => $default, ...$placeholder];
            }
            return [
                'year' => $placeholder,
                'month' => $placeholder,
                'day' => $placeholder,
            ];
        };

        $choiceTranslationDomainNormalizer = static function (Options $options, $choiceTranslationDomain) : array {
            if (\is_array($choiceTranslationDomain)) {
                $default = false;

                return array_replace(
                    ['year' => $default, 'month' => $default, 'day' => $default],
                    $choiceTranslationDomain
                );
            }
            return [
                'year' => $choiceTranslationDomain,
                'month' => $choiceTranslationDomain,
                'day' => $choiceTranslationDomain,
            ];
        };

        $format = static fn(Options $options): string|int => 'single_text' === $options['widget'] ? self::HTML5_FORMAT : self::DEFAULT_FORMAT;

        $resolver->setDefaults([
            'years' => range((int) date('Y') - 5, (int) date('Y') + 5),
            'months' => range(1, 12),
            'days' => range(1, 31),
            'widget' => 'choice',
            'input' => 'datetime',
            'format' => $format,
            'model_timezone' => null,
            'view_timezone' => null,
            'placeholder' => $placeholderDefault,
            'html5' => true,
            // Don't modify \DateTime classes by reference, we treat
            // them like immutable value objects
            'by_reference' => false,
            'error_bubbling' => false,
            // If initialized with a \DateTime object, FormType initializes
            // this option to "\DateTime". Since the internal, normalized
            // representation is not \DateTime, but an array, we need to unset
            // this option.
            'data_class' => null,
            'compound' => $compound,
            'empty_data' => static fn(Options $options): array|string => $options['compound'] ? [] : '',
            'choice_translation_domain' => false,
        ]);

        $resolver->setNormalizer('placeholder', $placeholderNormalizer);
        $resolver->setNormalizer('choice_translation_domain', $choiceTranslationDomainNormalizer);

        $resolver->setAllowedValues('input', [
            'datetime',
            'string',
            'timestamp',
            'array',
        ]);
        $resolver->setAllowedValues('widget', [
            'single_text',
            'text',
            'choice',
        ]);

        $resolver->setAllowedTypes('format', ['int', 'string']);
        $resolver->setAllowedTypes('years', 'array');
        $resolver->setAllowedTypes('months', 'array');
        $resolver->setAllowedTypes('days', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'date';
    }

    /**
     * @return mixed[]
     */
    private function formatTimestamps(\IntlDateFormatter $formatter, string $regex, array $timestamps): array
    {
        $pattern = $formatter->getPattern();
        $timezone = $formatter->getTimeZoneId();
        $formattedTimestamps = [];

        $formatter->setTimeZone('UTC');

        if (preg_match($regex, $pattern, $matches)) {
            $formatter->setPattern($matches[0]);

            foreach ($timestamps as $timestamp => $choice) {
                $formattedTimestamps[$formatter->format($timestamp)] = $choice;
            }

            // I'd like to clone the formatter above, but then we get a
            // segmentation fault, so let's restore the old state instead
            $formatter->setPattern($pattern);
        }

        $formatter->setTimeZone($timezone);

        return $formattedTimestamps;
    }

    /**
     * @return mixed[]
     */
    private function listYears(array $years): array
    {
        $result = [];

        foreach ($years as $year) {
            if (false !== $y = gmmktime(0, 0, 0, 6, 15, $year)) {
                $result[$y] = $year;
            }
        }

        return $result;
    }

    /**
     * @return mixed[]
     */
    private function listMonths(array $months): array
    {
        $result = [];

        foreach ($months as $month) {
            $result[gmmktime(0, 0, 0, $month, 15)] = $month;
        }

        return $result;
    }

    /**
     * @return mixed[]
     */
    private function listDays(array $days): array
    {
        $result = [];

        foreach ($days as $day) {
            $result[gmmktime(0, 0, 0, 5, $day)] = $day;
        }

        return $result;
    }
}