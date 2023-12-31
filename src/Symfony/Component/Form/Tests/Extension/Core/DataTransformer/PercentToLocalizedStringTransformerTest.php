<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\DataTransformer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\DataTransformer\PercentToLocalizedStringTransformer;
use Symfony\Component\Intl\Util\IntlTestHelper;

class PercentToLocalizedStringTransformerTest extends TestCase
{
    private $defaultLocale;

    protected function setUp()
    {
        $this->defaultLocale = \Locale::getDefault();
        \Locale::setDefault('en');
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->defaultLocale);
    }

    public function testTransform(): void
    {
        $transformer = new PercentToLocalizedStringTransformer();

        $this->assertEquals('10', $transformer->transform(0.1));
        $this->assertEquals('15', $transformer->transform(0.15));
        $this->assertEquals('12', $transformer->transform(0.1234));
        $this->assertEquals('200', $transformer->transform(2));
    }

    public function testTransformEmpty(): void
    {
        $transformer = new PercentToLocalizedStringTransformer();

        $this->assertEquals('', $transformer->transform(null));
    }

    public function testTransformWithInteger(): void
    {
        $transformer = new PercentToLocalizedStringTransformer(null, 'integer');

        $this->assertEquals('0', $transformer->transform(0.1));
        $this->assertEquals('1', $transformer->transform(1));
        $this->assertEquals('15', $transformer->transform(15));
        $this->assertEquals('16', $transformer->transform(15.9));
    }

    public function testTransformWithScale(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('de_AT');

        $transformer = new PercentToLocalizedStringTransformer(2);

        $this->assertEquals('12,34', $transformer->transform(0.1234));
    }

    public function testReverseTransform(): void
    {
        $transformer = new PercentToLocalizedStringTransformer();

        $this->assertEquals(0.1, $transformer->reverseTransform('10'));
        $this->assertEquals(0.15, $transformer->reverseTransform('15'));
        $this->assertEquals(0.12, $transformer->reverseTransform('12'));
        $this->assertEquals(2, $transformer->reverseTransform('200'));
    }

    public function testReverseTransformEmpty(): void
    {
        $transformer = new PercentToLocalizedStringTransformer();

        $this->assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransformWithInteger(): void
    {
        $transformer = new PercentToLocalizedStringTransformer(null, 'integer');

        $this->assertEquals(10, $transformer->reverseTransform('10'));
        $this->assertEquals(15, $transformer->reverseTransform('15'));
        $this->assertEquals(12, $transformer->reverseTransform('12'));
        $this->assertEquals(200, $transformer->reverseTransform('200'));
    }

    public function testReverseTransformWithScale(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('de_AT');

        $transformer = new PercentToLocalizedStringTransformer(2);

        $this->assertEquals(0.1234, $transformer->reverseTransform('12,34'));
    }

    public function testTransformExpectsNumeric(): void
    {
        $transformer = new PercentToLocalizedStringTransformer();

        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);

        $transformer->transform('foo');
    }

    public function testReverseTransformExpectsString(): void
    {
        $transformer = new PercentToLocalizedStringTransformer();

        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);

        $transformer->reverseTransform(1);
    }

    public function testDecimalSeparatorMayBeDotIfGroupingSeparatorIsNotDot(): void
    {
        IntlTestHelper::requireFullIntl($this, '4.8.1.1');

        \Locale::setDefault('fr');
        $transformer = new PercentToLocalizedStringTransformer(1, 'integer');

        // completely valid format
        $this->assertEquals(1234.5, $transformer->reverseTransform('1 234,5'));
        // accept dots
        $this->assertEquals(1234.5, $transformer->reverseTransform('1 234.5'));
        // omit group separator
        $this->assertEquals(1234.5, $transformer->reverseTransform('1234,5'));
        $this->assertEquals(1234.5, $transformer->reverseTransform('1234.5'));
    }

    public function testDecimalSeparatorMayNotBeDotIfGroupingSeparatorIsDot(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '4.8.1.1');

        \Locale::setDefault('de_DE');

        $transformer = new PercentToLocalizedStringTransformer(1, 'integer');

        $transformer->reverseTransform('1.234.5');
    }

    public function testDecimalSeparatorMayNotBeDotIfGroupingSeparatorIsDotWithNoGroupSep(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '4.8.1.1');

        \Locale::setDefault('de_DE');

        $transformer = new PercentToLocalizedStringTransformer(1, 'integer');

        $transformer->reverseTransform('1234.5');
    }

    public function testDecimalSeparatorMayBeDotIfGroupingSeparatorIsDotButNoGroupingUsed(): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('fr');
        $transformer = new PercentToLocalizedStringTransformer(1, 'integer');

        $this->assertEquals(1234.5, $transformer->reverseTransform('1234,5'));
        $this->assertEquals(1234.5, $transformer->reverseTransform('1234.5'));
    }

    public function testDecimalSeparatorMayBeCommaIfGroupingSeparatorIsNotComma(): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '4.8.1.1');

        \Locale::setDefault('bg');
        $transformer = new PercentToLocalizedStringTransformer(1, 'integer');

        // completely valid format
        $this->assertEquals(1234.5, $transformer->reverseTransform('1 234.5'));
        // accept commas
        $this->assertEquals(1234.5, $transformer->reverseTransform('1 234,5'));
        // omit group separator
        $this->assertEquals(1234.5, $transformer->reverseTransform('1234.5'));
        $this->assertEquals(1234.5, $transformer->reverseTransform('1234,5'));
    }

    public function testDecimalSeparatorMayNotBeCommaIfGroupingSeparatorIsComma(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        IntlTestHelper::requireFullIntl($this, '4.8.1.1');

        $transformer = new PercentToLocalizedStringTransformer(1, 'integer');

        $transformer->reverseTransform('1,234,5');
    }

    public function testDecimalSeparatorMayNotBeCommaIfGroupingSeparatorIsCommaWithNoGroupSep(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        IntlTestHelper::requireFullIntl($this, '4.8.1.1');

        $transformer = new PercentToLocalizedStringTransformer(1, 'integer');

        $transformer->reverseTransform('1234,5');
    }

    public function testDecimalSeparatorMayBeCommaIfGroupingSeparatorIsCommaButNoGroupingUsed(): void
    {
        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 1);
        $formatter->setAttribute(\NumberFormatter::GROUPING_USED, false);

        $transformer = $this->getMockBuilder(\Symfony\Component\Form\Extension\Core\DataTransformer\PercentToLocalizedStringTransformer::class)
            ->setMethods(['getNumberFormatter'])
            ->setConstructorArgs([1, 'integer'])
            ->getMock();
        $transformer->expects($this->any())
            ->method('getNumberFormatter')
            ->willReturn($formatter);

        $this->assertEquals(1234.5, $transformer->reverseTransform('1234,5'));
        $this->assertEquals(1234.5, $transformer->reverseTransform('1234.5'));
    }

    public function testReverseTransformDisallowsLeadingExtraCharacters(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $transformer = new PercentToLocalizedStringTransformer();

        $transformer->reverseTransform('foo123');
    }

    public function testReverseTransformDisallowsCenteredExtraCharacters(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo3"');
        $transformer = new PercentToLocalizedStringTransformer();

        $transformer->reverseTransform('12foo3');
    }

    /**
     * @requires extension mbstring
     */
    public function testReverseTransformDisallowsCenteredExtraCharactersMultibyte(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo8"');
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('ru');

        $transformer = new PercentToLocalizedStringTransformer();

        $transformer->reverseTransform("12\xc2\xa0345,67foo8");
    }

    public function testReverseTransformDisallowsTrailingExtraCharacters(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo"');
        $transformer = new PercentToLocalizedStringTransformer();

        $transformer->reverseTransform('123foo');
    }

    /**
     * @requires extension mbstring
     */
    public function testReverseTransformDisallowsTrailingExtraCharactersMultibyte(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo"');
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('ru');

        $transformer = new PercentToLocalizedStringTransformer();

        $transformer->reverseTransform("12\xc2\xa0345,678foo");
    }
}
