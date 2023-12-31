<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a card number belongs to a specified scheme.
 *
 * @author Tim Nagel <t.nagel@infinite.net.au>
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see https://en.wikipedia.org/wiki/Payment_card_number
 * @see https://www.regular-expressions.info/creditcard.html
 */
class CardSchemeValidator extends ConstraintValidator
{
    protected $schemes = [
        // American Express card numbers start with 34 or 37 and have 15 digits.
        'AMEX' => [
            '/^3[47]\d{13}$/',
        ],
        // China UnionPay cards start with 62 and have between 16 and 19 digits.
        // Please note that these cards do not follow Luhn Algorithm as a checksum.
        'CHINA_UNIONPAY' => [
            '/^62\d{14,17}$/',
        ],
        // Diners Club card numbers begin with 300 through 305, 36 or 38. All have 14 digits.
        // There are Diners Club cards that begin with 5 and have 16 digits.
        // These are a joint venture between Diners Club and MasterCard, and should be processed like a MasterCard.
        'DINERS' => [
            '/^3(?:0[0-5]|[68]\d)\d{11}$/',
        ],
        // Discover card numbers begin with 6011, 622126 through 622925, 644 through 649 or 65.
        // All have 16 digits.
        'DISCOVER' => [
            '/^6011\d{12}$/',
            '/^64[4-9]\d{13}$/',
            '/^65\d{14}$/',
            '/^622(12[6-9]|1[3-9]\d|[2-8]\d\d|91\d|92[0-5])\d{10}$/',
        ],
        // InstaPayment cards begin with 637 through 639 and have 16 digits.
        'INSTAPAYMENT' => [
            '/^63[7-9]\d{13}$/',
        ],
        // JCB cards beginning with 2131 or 1800 have 15 digits.
        // JCB cards beginning with 35 have 16 digits.
        'JCB' => [
            '/^(?:2131|1800|35\d{3})\d{11}$/',
        ],
        // Laser cards begin with either 6304, 6706, 6709 or 6771 and have between 16 and 19 digits.
        'LASER' => [
            '/^(6304|670[69]|6771)\d{12,15}$/',
        ],
        // Maestro international cards begin with 675900..675999 and have between 12 and 19 digits.
        // Maestro UK cards begin with either 500000..509999 or 560000..699999 and have between 12 and 19 digits.
        'MAESTRO' => [
            '/^(6759\d{2})\d{6,13}$/',
            '/^(50\d{4})\d{6,13}$/',
            '/^5[6-9]\d{10,17}$/',
            '/^6\d{11,18}$/',
        ],
        // All MasterCard numbers start with the numbers 51 through 55. All have 16 digits.
        // October 2016 MasterCard numbers can also start with 222100 through 272099.
        'MASTERCARD' => [
            '/^5[1-5]\d{14}$/',
            '/^2(22[1-9]\d{12}|2[3-9]\d{13}|[3-6]\d{14}|7[0-1]\d{13}|720\d{12})$/',
        ],
        // All Visa card numbers start with a 4 and have a length of 13, 16, or 19 digits.
        'VISA' => [
            '/^4(\d{12}|\d{15}|\d{18})$/',
        ],
    ];

    /**
     * Validates a creditcard belongs to a specified scheme.
     *
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CardScheme) {
            throw new UnexpectedTypeException($constraint, CardScheme::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_numeric($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(CardScheme::NOT_NUMERIC_ERROR)
                ->addViolation();

            return;
        }

        $schemes = array_flip((array) $constraint->schemes);
        $schemeRegexes = array_intersect_key($this->schemes, $schemes);

        foreach ($schemeRegexes as $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $value)) {
                    return;
                }
            }
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setCode(CardScheme::INVALID_FORMAT_ERROR)
            ->addViolation();
    }
}
