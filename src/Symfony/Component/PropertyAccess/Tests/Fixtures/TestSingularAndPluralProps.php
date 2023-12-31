<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyAccess\Tests\Fixtures;

/**
 * Notice we don't have getter/setter for emails
 * because we count on adder/remover.
 */
class TestSingularAndPluralProps
{
    /** @var string|null */
    private $email;

    private array $emails = [];

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * @param string $email
     */
    public function addEmail($email): void
    {
        $this->emails[] = $email;
    }

    /**
     * @param string $email
     */
    public function removeEmail($email): void
    {
        $this->emails = array_diff($this->emails, [$email]);
    }
}
