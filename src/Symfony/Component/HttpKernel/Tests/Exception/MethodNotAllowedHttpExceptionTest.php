<?php

namespace Symfony\Component\HttpKernel\Tests\Exception;

use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class MethodNotAllowedHttpExceptionTest extends HttpExceptionTest
{
    public function testHeadersDefault(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET', 'PUT']);
        $this->assertSame(['Allow' => 'GET, PUT'], $exception->getHeaders());
    }

    /**
     * @dataProvider headerDataProvider
     */
    public function testHeadersSetter($headers): void
    {
        $exception = new MethodNotAllowedHttpException(['GET']);
        $exception->setHeaders($headers);
        $this->assertSame($headers, $exception->getHeaders());
    }
}
