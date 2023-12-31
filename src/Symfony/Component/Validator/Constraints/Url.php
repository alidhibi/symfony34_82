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

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Url extends Constraint
{
    final const CHECK_DNS_TYPE_ANY = 'ANY';

    final const CHECK_DNS_TYPE_NONE = false;

    final const CHECK_DNS_TYPE_A = 'A';

    final const CHECK_DNS_TYPE_A6 = 'A6';

    final const CHECK_DNS_TYPE_AAAA = 'AAAA';

    final const CHECK_DNS_TYPE_CNAME = 'CNAME';

    final const CHECK_DNS_TYPE_MX = 'MX';

    final const CHECK_DNS_TYPE_NAPTR = 'NAPTR';

    final const CHECK_DNS_TYPE_NS = 'NS';

    final const CHECK_DNS_TYPE_PTR = 'PTR';

    final const CHECK_DNS_TYPE_SOA = 'SOA';

    final const CHECK_DNS_TYPE_SRV = 'SRV';

    final const CHECK_DNS_TYPE_TXT = 'TXT';

    final const INVALID_URL_ERROR = '57c2f299-1154-4870-89bb-ef3b1f5ad229';

    protected static $errorNames = [
        self::INVALID_URL_ERROR => 'INVALID_URL_ERROR',
    ];

    public $message = 'This value is not a valid URL.';

    public $dnsMessage = 'The host could not be resolved.';

    public $protocols = ['http', 'https'];

    public $checkDNS = self::CHECK_DNS_TYPE_NONE;
}
