<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Tests\Encoder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class MessageDigestPasswordEncoderTest extends TestCase
{
    public function testIsPasswordValid(): void
    {
        $encoder = new MessageDigestPasswordEncoder('sha256', false, 1);

        $this->assertTrue($encoder->isPasswordValid(hash('sha256', 'password'), 'password', ''));
    }

    public function testEncodePassword(): void
    {
        $encoder = new MessageDigestPasswordEncoder('sha256', false, 1);
        $this->assertSame(hash('sha256', 'password'), $encoder->encodePassword('password', ''));

        $encoder = new MessageDigestPasswordEncoder('sha256', true, 1);
        $this->assertSame(base64_encode(hash('sha256', 'password', true)), $encoder->encodePassword('password', ''));

        $encoder = new MessageDigestPasswordEncoder('sha256', false, 2);
        $this->assertSame(hash('sha256', hash('sha256', 'password', true).'password'), $encoder->encodePassword('password', ''));
    }

    public function testEncodePasswordAlgorithmDoesNotExist(): void
    {
        $this->expectException('LogicException');
        $encoder = new MessageDigestPasswordEncoder('foobar');
        $encoder->encodePassword('password', '');
    }

    public function testEncodePasswordLength(): void
    {
        $this->expectException(\Symfony\Component\Security\Core\Exception\BadCredentialsException::class);
        $encoder = new MessageDigestPasswordEncoder();

        $encoder->encodePassword(str_repeat('a', 5000), 'salt');
    }

    public function testCheckPasswordLength(): void
    {
        $encoder = new MessageDigestPasswordEncoder();

        $this->assertFalse($encoder->isPasswordValid('encoded', str_repeat('a', 5000), 'salt'));
    }
}
