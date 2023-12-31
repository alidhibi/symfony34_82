<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\EventListener;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class FirewallListener extends Firewall
{
    private readonly \Symfony\Component\Security\Http\FirewallMapInterface $map;

    private readonly \Symfony\Component\Security\Http\Logout\LogoutUrlGenerator $logoutUrlGenerator;

    public function __construct(FirewallMapInterface $map, EventDispatcherInterface $dispatcher, LogoutUrlGenerator $logoutUrlGenerator)
    {
        $this->map = $map;
        $this->logoutUrlGenerator = $logoutUrlGenerator;

        parent::__construct($map, $dispatcher);
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->map instanceof FirewallMap && $config = $this->map->getFirewallConfig($event->getRequest())) {
            $this->logoutUrlGenerator->setCurrentFirewall($config->getName(), $config->getContext());
        }

        parent::onKernelRequest($event);
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        if ($event->isMasterRequest()) {
            $this->logoutUrlGenerator->setCurrentFirewall(null);
        }

        parent::onKernelFinishRequest($event);
    }
}
