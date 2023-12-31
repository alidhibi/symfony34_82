<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Tests\Authorization\Voter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AuthenticatedVoterTest extends TestCase
{
    /**
     * @dataProvider getVoteTests
     */
    public function testVote(string $authenticated, array $attributes, int $expected): void
    {
        $voter = new AuthenticatedVoter($this->getResolver());

        $this->assertSame($expected, $voter->vote($this->getToken($authenticated), null, $attributes));
    }

    public function getVoteTests(): array
    {
        return [
            ['fully', [], VoterInterface::ACCESS_ABSTAIN],
            ['fully', ['FOO'], VoterInterface::ACCESS_ABSTAIN],
            ['remembered', [], VoterInterface::ACCESS_ABSTAIN],
            ['remembered', ['FOO'], VoterInterface::ACCESS_ABSTAIN],
            ['anonymously', [], VoterInterface::ACCESS_ABSTAIN],
            ['anonymously', ['FOO'], VoterInterface::ACCESS_ABSTAIN],

            ['fully', ['IS_AUTHENTICATED_ANONYMOUSLY'], VoterInterface::ACCESS_GRANTED],
            ['remembered', ['IS_AUTHENTICATED_ANONYMOUSLY'], VoterInterface::ACCESS_GRANTED],
            ['anonymously', ['IS_AUTHENTICATED_ANONYMOUSLY'], VoterInterface::ACCESS_GRANTED],

            ['fully', ['IS_AUTHENTICATED_REMEMBERED'], VoterInterface::ACCESS_GRANTED],
            ['remembered', ['IS_AUTHENTICATED_REMEMBERED'], VoterInterface::ACCESS_GRANTED],
            ['anonymously', ['IS_AUTHENTICATED_REMEMBERED'], VoterInterface::ACCESS_DENIED],

            ['fully', ['IS_AUTHENTICATED_FULLY'], VoterInterface::ACCESS_GRANTED],
            ['remembered', ['IS_AUTHENTICATED_FULLY'], VoterInterface::ACCESS_DENIED],
            ['anonymously', ['IS_AUTHENTICATED_FULLY'], VoterInterface::ACCESS_DENIED],
        ];
    }

    protected function getResolver(): \Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver
    {
        return new AuthenticationTrustResolver(
            \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken::class,
            \Symfony\Component\Security\Core\Authentication\Token\RememberMeToken::class
        );
    }

    protected function getToken($authenticated)
    {
        if ('fully' === $authenticated) {
            return $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock();
        } elseif ('remembered' === $authenticated) {
            return $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\RememberMeToken::class)->setMethods(['setPersistent'])->disableOriginalConstructor()->getMock();
        } else {
            return $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\AnonymousToken::class)->setConstructorArgs(['', ''])->getMock();
        }
    }
}
