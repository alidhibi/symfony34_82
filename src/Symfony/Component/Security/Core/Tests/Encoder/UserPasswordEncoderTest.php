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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserPasswordEncoderTest extends TestCase
{
    public function testEncodePassword(): void
    {
        $userMock = $this->getMockBuilder(\Symfony\Component\Security\Core\User\UserInterface::class)->getMock();
        $userMock->expects($this->any())
            ->method('getSalt')
            ->willReturn('userSalt');

        $mockEncoder = $this->getMockBuilder(\Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface::class)->getMock();
        $mockEncoder->expects($this->any())
            ->method('encodePassword')
            ->with($this->equalTo('plainPassword'), $this->equalTo('userSalt'))
            ->willReturn('encodedPassword');

        $mockEncoderFactory = $this->getMockBuilder(\Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface::class)->getMock();
        $mockEncoderFactory->expects($this->any())
            ->method('getEncoder')
            ->with($this->equalTo($userMock))
            ->willReturn($mockEncoder);

        $passwordEncoder = new UserPasswordEncoder($mockEncoderFactory);

        $encoded = $passwordEncoder->encodePassword($userMock, 'plainPassword');
        $this->assertEquals('encodedPassword', $encoded);
    }

    public function testIsPasswordValid(): void
    {
        $userMock = $this->getMockBuilder(\Symfony\Component\Security\Core\User\UserInterface::class)->getMock();
        $userMock->expects($this->any())
            ->method('getSalt')
            ->willReturn('userSalt');
        $userMock->expects($this->any())
            ->method('getPassword')
            ->willReturn('encodedPassword');

        $mockEncoder = $this->getMockBuilder(\Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface::class)->getMock();
        $mockEncoder->expects($this->any())
            ->method('isPasswordValid')
            ->with($this->equalTo('encodedPassword'), $this->equalTo('plainPassword'), $this->equalTo('userSalt'))
            ->willReturn(true);

        $mockEncoderFactory = $this->getMockBuilder(\Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface::class)->getMock();
        $mockEncoderFactory->expects($this->any())
            ->method('getEncoder')
            ->with($this->equalTo($userMock))
            ->willReturn($mockEncoder);

        $passwordEncoder = new UserPasswordEncoder($mockEncoderFactory);

        $isValid = $passwordEncoder->isPasswordValid($userMock, 'plainPassword');
        $this->assertTrue($isValid);
    }
}
