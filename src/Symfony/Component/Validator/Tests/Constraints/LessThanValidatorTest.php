<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests\Constraints;

use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanValidator;

/**
 * @author Daniel Holmes <daniel@danielholmes.org>
 */
class LessThanValidatorTest extends AbstractComparisonValidatorTestCase
{
    protected function createValidator(): \Symfony\Component\Validator\Constraints\LessThanValidator
    {
        return new LessThanValidator();
    }

    protected function createConstraint(array $options = null): \Symfony\Component\Validator\Constraints\LessThan
    {
        return new LessThan($options);
    }

    protected function getErrorCode(): string
    {
        return LessThan::TOO_HIGH_ERROR;
    }

    /**
     * {@inheritdoc}
     */
    public function provideValidComparisons(): array
    {
        return [
            [1, 2],
            [new \DateTime('2000-01-01'), new \DateTime('2010-01-01')],
            [new \DateTime('2000-01-01'), '2010-01-01'],
            [new \DateTime('2000-01-01 UTC'), '2010-01-01 UTC'],
            [new ComparisonTest_Class(4), new ComparisonTest_Class(5)],
            ['22', '333'],
            [null, 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideValidComparisonsToPropertyPath(): array
    {
        return [
            [4],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideInvalidComparisons(): array
    {
        return [
            [3, '3', 2, '2', 'integer'],
            [2, '2', 2, '2', 'integer'],
            [new \DateTime('2010-01-01'), 'Jan 1, 2010, 12:00 AM', new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new \DateTime('2010-01-01'), 'Jan 1, 2010, 12:00 AM', '2000-01-01', 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', '2000-01-01', 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new \DateTime('2010-01-01 UTC'), 'Jan 1, 2010, 12:00 AM', '2000-01-01 UTC', 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new \DateTime('2000-01-01 UTC'), 'Jan 1, 2000, 12:00 AM', '2000-01-01 UTC', 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new ComparisonTest_Class(5), '5', new ComparisonTest_Class(5), '5', __NAMESPACE__.'\ComparisonTest_Class'],
            [new ComparisonTest_Class(6), '6', new ComparisonTest_Class(5), '5', __NAMESPACE__.'\ComparisonTest_Class'],
            ['333', '"333"', '22', '"22"', 'string'],
        ];
    }

    public function provideComparisonsToNullValueAtPropertyPath(): array
    {
        return [
            [5, '5', true],
        ];
    }
}
