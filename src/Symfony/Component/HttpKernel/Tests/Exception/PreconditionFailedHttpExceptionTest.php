<?php

namespace Symfony\Component\HttpKernel\Tests\Exception;

use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class PreconditionFailedHttpExceptionTest extends HttpExceptionTest
{
    protected function createException(): \Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException
    {
        return new PreconditionFailedHttpException();
    }
}
