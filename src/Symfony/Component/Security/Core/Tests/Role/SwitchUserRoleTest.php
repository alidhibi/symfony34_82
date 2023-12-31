<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Tests\Role;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Role\SwitchUserRole;

class SwitchUserRoleTest extends TestCase
{
    public function testGetSource(): void
    {
        $role = new SwitchUserRole('FOO', $token = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock());

        $this->assertSame($token, $role->getSource());
    }

    public function testGetRole(): void
    {
        $role = new SwitchUserRole('FOO', $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock());

        $this->assertEquals('FOO', $role->getRole());
    }
}
