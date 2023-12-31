<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\EntryPoint;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;

/**
 * DigestAuthenticationEntryPoint starts an HTTP Digest authentication.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since 3.4, to be removed in 4.0
 */
class DigestAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $secret;

    private $realmName;

    private $nonceValiditySeconds;

    private ?\Psr\Log\LoggerInterface $logger = null;

    public function __construct($realmName, $secret, $nonceValiditySeconds = 300, LoggerInterface $logger = null)
    {
        @trigger_error(sprintf('The %s class and the whole HTTP digest authentication system is deprecated since Symfony 3.4 and will be removed in 4.0.', __CLASS__), \E_USER_DEPRECATED);

        $this->realmName = $realmName;
        $this->secret = $secret;
        $this->nonceValiditySeconds = $nonceValiditySeconds;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null): \Symfony\Component\HttpFoundation\Response
    {
        $expiryTime = microtime(true) + $this->nonceValiditySeconds * 1000;
        $signatureValue = md5($expiryTime.':'.$this->secret);
        $nonceValue = $expiryTime.':'.$signatureValue;
        $nonceValueBase64 = base64_encode($nonceValue);

        $authenticateHeader = sprintf('Digest realm="%s", qop="auth", nonce="%s"', $this->realmName, $nonceValueBase64);

        if ($authException instanceof NonceExpiredException) {
            $authenticateHeader .= ', stale="true"';
        }

        if ($this->logger instanceof \Psr\Log\LoggerInterface) {
            $this->logger->debug('WWW-Authenticate header sent.', ['header' => $authenticateHeader]);
        }

        $response = new Response();
        $response->headers->set('WWW-Authenticate', $authenticateHeader);
        $response->setStatusCode(401);

        return $response;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getRealmName()
    {
        return $this->realmName;
    }
}
