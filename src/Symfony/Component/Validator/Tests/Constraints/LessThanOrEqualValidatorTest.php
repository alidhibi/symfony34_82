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

use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqualValidator;

/**
 * @author Daniel Holmes <daniel@danielholmes.org>
 */
class LessThanOrEqualValidatorTest extends AbstractComparisonValidatorTestCase
{
    protected function createValidator(): \Symfony\Component\Validator\Constraints\LessThanOrEqualValidator
    {
        return new LessThanOrEqualValidator();
    }

    protected function createConstraint(array $options = null): \Symfony\Component\Validator\Constraints\LessThanOrEqual
    {
        return new LessThanOrEqual($options);
    }

    protected function getErrorCode(): string
    {
        return LessThanOrEqual::TOO_HIGH_ERROR;
    }

    /**
     * {@inheritdoc}
     */
    public function provideValidComparisons(): array
    {
        return [
            [1, 2],
            [1, 1],
            [new \DateTime('2000-01-01'), new \DateTime('2000-01-01')],
            [new \DateTime('2000-01-01'), new \DateTime('2020-01-01')],
            [new \DateTime('2000-01-01'), '2000-01-01'],
            [new \DateTime('2000-01-01'), '2020-01-01'],
            [new \DateTime('2000-01-01 UTC'), '2000-01-01 UTC'],
            [new \DateTime('2000-01-01 UTC'), '2020-01-01 UTC'],
            [new ComparisonTest_Class(4), new ComparisonTest_Class(5)],
            [new ComparisonTest_Class(5), new ComparisonTest_Class(5)],
            ['a', 'a'],
            ['a', 'z'],
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
            [5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideInvalidComparisons(): array
    {
        return [
            [2, '2', 1, '1', 'integer'],
            [new \DateTime('2010-01-01'), 'Jan 1, 2010, 12:00 AM', new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new \DateTime('2010-01-01'), 'Jan 1, 2010, 12:00 AM', '2000-01-01', 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new \DateTime('2010-01-01 UTC'), 'Jan 1, 2010, 12:00 AM', '2000-01-01 UTC', 'Jan 1, 2000, 12:00 AM', 'DateTime'],
            [new ComparisonTest_Class(5), '5', new ComparisonTest_Class(4), '4', __NAMESPACE__.'\ComparisonTest_Class'],
            ['c', '"c"', 'b', '"b"', 'string'],
        ];
    }

    public function provideComparisonsToNullValueAtPropertyPath(): array
    {
        return [
            [5, '5', true],
        ];
    }
}
