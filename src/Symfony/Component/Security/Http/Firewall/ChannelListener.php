<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Firewall;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * ChannelListener switches the HTTP protocol based on the access control
 * configuration.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ChannelListener implements ListenerInterface
{
    private readonly \Symfony\Component\Security\Http\AccessMapInterface $map;

    private readonly \Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface $authenticationEntryPoint;

    private ?\Psr\Log\LoggerInterface $logger = null;

    public function __construct(AccessMapInterface $map, AuthenticationEntryPointInterface $authenticationEntryPoint, LoggerInterface $logger = null)
    {
        $this->map = $map;
        $this->authenticationEntryPoint = $authenticationEntryPoint;
        $this->logger = $logger;
    }

    /**
     * Handles channel management.
     */
    public function handle(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        list(, $channel) = $this->map->getPatterns($request);

        if ('https' === $channel && !$request->isSecure()) {
            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->info('Redirecting to HTTPS.');
            }

            $response = $this->authenticationEntryPoint->start($request);

            $event->setResponse($response);

            return;
        }

        if ('http' === $channel && $request->isSecure()) {
            if ($this->logger instanceof \Psr\Log\LoggerInterface) {
                $this->logger->info('Redirecting to HTTP.');
            }

            $response = $this->authenticationEntryPoint->start($request);

            $event->setResponse($response);
        }
    }
}
