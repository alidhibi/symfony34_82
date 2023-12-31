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
use Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\Role;

class ExpressionVoterTest extends TestCase
{
    /**
     * @dataProvider getVoteTests
     */
    public function testVote(array $roles, array $attributes, int $expected, bool $tokenExpectsGetRoles = true, bool $expressionLanguageExpectsEvaluate = true): void
    {
        $voter = new ExpressionVoter($this->createExpressionLanguage($expressionLanguageExpectsEvaluate), $this->createTrustResolver());

        $this->assertSame($expected, $voter->vote($this->getToken($roles, $tokenExpectsGetRoles), null, $attributes));
    }

    public function getVoteTests(): array
    {
        return [
            [[], [], VoterInterface::ACCESS_ABSTAIN, false, false],
            [[], ['FOO'], VoterInterface::ACCESS_ABSTAIN, false, false],

            [[], [$this->createExpression()], VoterInterface::ACCESS_DENIED, true, false],

            [['ROLE_FOO'], [$this->createExpression(), $this->createExpression()], VoterInterface::ACCESS_GRANTED],
            [['ROLE_BAR', 'ROLE_FOO'], [$this->createExpression()], VoterInterface::ACCESS_GRANTED],
        ];
    }

    protected function getToken(array $roles, $tokenExpectsGetRoles = true)
    {
        foreach ($roles as $i => $role) {
            $roles[$i] = new Role($role);
        }

        $token = $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class)->getMock();

        if ($tokenExpectsGetRoles) {
            $token->expects($this->once())
                ->method('getRoles')
                ->willReturn($roles);
        }

        return $token;
    }

    protected function createExpressionLanguage($expressionLanguageExpectsEvaluate = true)
    {
        $mock = $this->getMockBuilder(\Symfony\Component\Security\Core\Authorization\ExpressionLanguage::class)->getMock();

        if ($expressionLanguageExpectsEvaluate) {
            $mock->expects($this->once())
                ->method('evaluate')
                ->willReturn(true);
        }

        return $mock;
    }

    protected function createTrustResolver()
    {
        return $this->getMockBuilder(\Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface::class)->getMock();
    }

    protected function createRoleHierarchy()
    {
        return $this->getMockBuilder(\Symfony\Component\Security\Core\Role\RoleHierarchyInterface::class)->getMock();
    }

    protected function createExpression()
    {
        return $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\Expression::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
