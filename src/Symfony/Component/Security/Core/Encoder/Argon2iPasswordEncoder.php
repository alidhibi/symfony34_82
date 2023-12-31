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

use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Argon2iPasswordEncoder uses the Argon2i hashing algorithm.
 *
 * @author Zan Baldwin <hello@zanbaldwin.com>
 */
class Argon2iPasswordEncoder extends BasePasswordEncoder implements SelfSaltingEncoderInterface
{
    public static function isSupported(): bool|int
    {
        if (\PHP_VERSION_ID >= 70200 && \defined('PASSWORD_ARGON2I')) {
            return true;
        }

        return version_compare(\extension_loaded('sodium') ? \SODIUM_LIBRARY_VERSION : phpversion('libsodium'), '1.0.9', '>=');
    }

    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        if (\PHP_VERSION_ID >= 70200 && \defined('PASSWORD_ARGON2I')) {
            return $this->encodePasswordNative($raw);
        }

        if (\function_exists('sodium_crypto_pwhash_str')) {
            return $this->encodePasswordSodiumFunction($raw);
        }

        if (\extension_loaded('libsodium')) {
            return $this->encodePasswordSodiumExtension($raw);
        }

        throw new \LogicException('Argon2i algorithm is not supported. Please install the libsodium extension or upgrade to PHP 7.2+.');
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        // If $encoded was created via "sodium_crypto_pwhash_str()", the hashing algorithm may be "argon2id" instead of "argon2i".
        // In this case, "password_verify()" cannot be used.
        if (\PHP_VERSION_ID >= 70200 && \defined('PASSWORD_ARGON2I') && (false === strpos($encoded, '$argon2id$'))) {
            return !$this->isPasswordTooLong($raw) && password_verify($raw, $encoded);
        }

        if (\function_exists('sodium_crypto_pwhash_str_verify')) {
            $valid = !$this->isPasswordTooLong($raw) && sodium_crypto_pwhash_str_verify($encoded, $raw);
            sodium_memzero($raw);

            return $valid;
        }

        if (\extension_loaded('libsodium')) {
            $valid = !$this->isPasswordTooLong($raw) && \Sodium\crypto_pwhash_str_verify($encoded, $raw);
            \Sodium\memzero($raw);

            return $valid;
        }

        throw new \LogicException('Argon2i algorithm is not supported. Please install the libsodium extension or upgrade to PHP 7.2+.');
    }

    private function encodePasswordNative($raw): string
    {
        return password_hash($raw, \PASSWORD_ARGON2I);
    }

    private function encodePasswordSodiumFunction($raw): string
    {
        $hash = sodium_crypto_pwhash_str(
            $raw,
            \SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            \SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        sodium_memzero($raw);

        return $hash;
    }

    private function encodePasswordSodiumExtension($raw): string
    {
        $hash = \Sodium\crypto_pwhash_str(
            $raw,
            \Sodium\CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            \Sodium\CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        \Sodium\memzero($raw);

        return $hash;
    }
}
