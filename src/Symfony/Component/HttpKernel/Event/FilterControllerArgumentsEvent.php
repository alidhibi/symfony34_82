<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Allows filtering of controller arguments.
 *
 * You can call getController() to retrieve the controller and getArguments
 * to retrieve the current arguments. With setArguments() you can replace
 * arguments that are used to call the controller.
 *
 * Arguments set in the event must be compatible with the signature of the
 * controller.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class FilterControllerArgumentsEvent extends FilterControllerEvent
{
    private array $arguments;

    public function __construct(HttpKernelInterface $kernel, callable $controller, array $arguments, Request $request, $requestType)
    {
        parent::__construct($kernel, $controller, $request, $requestType);

        $this->arguments = $arguments;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }
}
