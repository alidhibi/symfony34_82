<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Tests\Authentication\Token;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Role\Role;

class PreAuthenticatedTokenTest extends TestCase
{
    public function testConstructor(): void
    {
        $token = new PreAuthenticatedToken('foo', 'bar', 'key');
        $this->assertFalse($token->isAuthenticated());

        $token = new PreAuthenticatedToken('foo', 'bar', 'key', ['ROLE_FOO']);
        $this->assertTrue($token->isAuthenticated());
        $this->assertEquals([new Role('ROLE_FOO')], $token->getRoles());
        $this->assertEquals('key', $token->getProviderKey());
    }

    public function testGetCredentials(): void
    {
        $token = new PreAuthenticatedToken('foo', 'bar', 'key');
        $this->assertEquals('bar', $token->getCredentials());
    }

    public function testGetUser(): void
    {
        $token = new PreAuthenticatedToken('foo', 'bar', 'key');
        $this->assertEquals('foo', $token->getUser());
    }

    public function testEraseCredentials(): void
    {
        $token = new PreAuthenticatedToken('foo', 'bar', 'key');
        $token->eraseCredentials();
        $this->assertEquals('', $token->getCredentials());
    }
}
