<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\Type;

use Symfony\Component\Intl\Util\IntlTestHelper;

class IntegerTypeTest extends BaseTypeTest
{
    final const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\IntegerType';

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this, false);

        parent::setUp();
    }

    public function testSubmitRejectsFloats(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE);

        $form->submit('1.678');

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertFalse($form->isSynchronized());
    }

    public function testSubmitNull($expected = null, $norm = null, $view = null): void
    {
        parent::testSubmitNull($expected, $norm, '');
    }

    public function testSubmitNullUsesDefaultEmptyData($emptyData = '10', $expectedData = 10): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, [
            'empty_data' => $emptyData,
        ]);
        $form->submit(null);

        $this->assertSame($emptyData, $form->getViewData());
        $this->assertSame($expectedData, $form->getNormData());
        $this->assertSame($expectedData, $form->getData());
    }

    public function testSubmittedLargeIntegersAreNotCastToFloat(): void
    {
        if (4 === \PHP_INT_SIZE) {
            $this->markTestSkipped('This test requires a 64bit PHP.');
        }

        $form = $this->factory->create(static::TESTED_TYPE);
        $form->submit('201803221011791');

        $this->assertSame(201_803_221_011_791, $form->getData());
        $this->assertSame('201803221011791', $form->getViewData());
    }

    public function testTooSmallIntegersAreNotValid(): void
    {
        $min = 4 === \PHP_INT_SIZE ? '-2147483649' : '-9223372036854775808';

        $form = $this->factory->create(static::TESTED_TYPE);
        $form->submit($min);

        $this->assertFalse($form->isSynchronized());
    }

    public function testTooGreatIntegersAreNotValid(): void
    {
        $max = 4 === \PHP_INT_SIZE ? '2147483648' : '9223372036854775808';

        $form = $this->factory->create(static::TESTED_TYPE);
        $form->submit($max);

        $this->assertFalse($form->isSynchronized());
    }
}
