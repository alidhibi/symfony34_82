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
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

class PasswordEncoder extends BasePasswordEncoder
{
    public function encodePassword($raw, $salt): void
    {
    }

    public function isPasswordValid($encoded, $raw, $salt): void
    {
    }
}

class BasePasswordEncoderTest extends TestCase
{
    public function testComparePassword(): void
    {
        $this->assertTrue($this->invokeComparePasswords('password', 'password'));
        $this->assertFalse($this->invokeComparePasswords('password', 'foo'));
    }

    public function testDemergePasswordAndSalt(): void
    {
        $this->assertEquals(['password', 'salt'], $this->invokeDemergePasswordAndSalt('password{salt}'));
        $this->assertEquals(['password', ''], $this->invokeDemergePasswordAndSalt('password'));
        $this->assertEquals(['', ''], $this->invokeDemergePasswordAndSalt(''));
    }

    public function testMergePasswordAndSalt(): void
    {
        $this->assertEquals('password{salt}', $this->invokeMergePasswordAndSalt('password', 'salt'));
        $this->assertEquals('password', $this->invokeMergePasswordAndSalt('password', ''));
    }

    public function testMergePasswordAndSaltWithException(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->invokeMergePasswordAndSalt('password', '{foo}');
    }

    public function testIsPasswordTooLong(): void
    {
        $this->assertTrue($this->invokeIsPasswordTooLong(str_repeat('a', 10000)));
        $this->assertFalse($this->invokeIsPasswordTooLong(str_repeat('a', 10)));
    }

    protected function invokeDemergePasswordAndSalt($password)
    {
        $encoder = new PasswordEncoder();
        $r = new \ReflectionObject($encoder);
        $m = $r->getMethod('demergePasswordAndSalt');
        $m->setAccessible(true);

        return $m->invoke($encoder, $password);
    }

    protected function invokeMergePasswordAndSalt($password, $salt)
    {
        $encoder = new PasswordEncoder();
        $r = new \ReflectionObject($encoder);
        $m = $r->getMethod('mergePasswordAndSalt');
        $m->setAccessible(true);

        return $m->invoke($encoder, $password, $salt);
    }

    protected function invokeComparePasswords($p1, $p2)
    {
        $encoder = new PasswordEncoder();
        $r = new \ReflectionObject($encoder);
        $m = $r->getMethod('comparePasswords');
        $m->setAccessible(true);

        return $m->invoke($encoder, $p1, $p2);
    }

    protected function invokeIsPasswordTooLong($p)
    {
        $encoder = new PasswordEncoder();
        $r = new \ReflectionObject($encoder);
        $m = $r->getMethod('isPasswordTooLong');
        $m->setAccessible(true);

        return $m->invoke($encoder, $p);
    }
}
