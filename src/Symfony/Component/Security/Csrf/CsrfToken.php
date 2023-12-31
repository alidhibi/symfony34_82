<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Csrf;

/**
 * A CSRF token.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CsrfToken
{
    private readonly string $id;

    private readonly string $value;

    /**
     * @param string $id    The token ID
     * @param string $value The actual token value
     */
    public function __construct($id, $value)
    {
        $this->id = (string) $id;
        $this->value = (string) $value;
    }

    /**
     * Returns the ID of the CSRF token.
     *
     * @return string The token ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the value of the CSRF token.
     *
     * @return string The token value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Returns the value of the CSRF token.
     *
     * @return string The token value
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
