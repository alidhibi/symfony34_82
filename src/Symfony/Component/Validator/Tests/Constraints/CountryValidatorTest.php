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

use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\CountryValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class CountryValidatorTest extends ConstraintValidatorTestCase
{
    private $defaultLocale;

    protected function setUp()
    {
        parent::setUp();

        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Locale::setDefault($this->defaultLocale);
    }

    protected function createValidator(): \Symfony\Component\Validator\Constraints\CountryValidator
    {
        return new CountryValidator();
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new Country());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new Country());

        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new Country());
    }

    /**
     * @dataProvider getValidCountries
     */
    public function testValidCountries(string $country): void
    {
        $this->validator->validate($country, new Country());

        $this->assertNoViolation();
    }

    public function getValidCountries(): array
    {
        return [
            ['GB'],
            ['AT'],
            ['MY'],
        ];
    }

    /**
     * @dataProvider getInvalidCountries
     */
    public function testInvalidCountries(string $country): void
    {
        $constraint = new Country([
            'message' => 'myMessage',
        ]);

        $this->validator->validate($country, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$country.'"')
            ->setCode(Country::NO_SUCH_COUNTRY_ERROR)
            ->assertRaised();
    }

    public function getInvalidCountries(): array
    {
        return [
            ['foobar'],
            ['EN'],
        ];
    }

    public function testValidateUsingCountrySpecificLocale(): void
    {
        // in order to test with "en_GB"
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('en_GB');

        $existingCountry = 'GB';

        $this->validator->validate($existingCountry, new Country());

        $this->assertNoViolation();
    }
}
