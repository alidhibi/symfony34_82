<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Encoder;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A generic password encoder.
 *
 * @author Ariel Ferrandini <arielferrandini@gmail.com>
 */
class UserPasswordEncoder implements UserPasswordEncoderInterface
{
    private readonly \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function encodePassword(UserInterface $user, $plainPassword)
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid(UserInterface $user, $raw)
    {
        if (null === $user->getPassword()) {
            return false;
        }

        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->isPasswordValid($user->getPassword(), $raw, $user->getSalt());
    }
}
