<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyAccess\Tests;

class TestPluralAdderRemoverAndSetter
{
    private array $emails = [];

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function setEmails(array $emails): void
    {
        $this->emails = ['foo@email.com'];
    }

    public function addEmail($email): void
    {
        $this->emails[] = $email;
    }

    public function removeEmail($email): void
    {
        $this->emails = array_diff($this->emails, [$email]);
    }
}
