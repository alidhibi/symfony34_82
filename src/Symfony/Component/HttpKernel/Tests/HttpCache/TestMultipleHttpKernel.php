<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\HttpCache;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TestMultipleHttpKernel extends HttpKernel implements ControllerResolverInterface, ArgumentResolverInterface
{
    protected $bodies = [];

    protected $statuses = [];

    protected $headers = [];

    protected $called = false;

    protected $backendRequest;

    public function __construct($responses)
    {
        foreach ($responses as $response) {
            $this->bodies[] = $response['body'];
            $this->statuses[] = $response['status'];
            $this->headers[] = $response['headers'];
        }

        parent::__construct(new EventDispatcher(), $this, null, $this);
    }

    public function getBackendRequest()
    {
        return $this->backendRequest;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = false)
    {
        $this->backendRequest = $request;

        return parent::handle($request, $type, $catch);
    }

    public function getController(Request $request): array
    {
        return fn(\Symfony\Component\HttpFoundation\Request $request) => $this->callController($request);
    }

    public function getArguments(Request $request, $controller): array
    {
        return [$request];
    }

    public function callController(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $this->called = true;

        return new Response(array_shift($this->bodies), array_shift($this->statuses), array_shift($this->headers));
    }

    public function hasBeenCalled()
    {
        return $this->called;
    }

    public function reset(): void
    {
        $this->called = false;
    }
}
