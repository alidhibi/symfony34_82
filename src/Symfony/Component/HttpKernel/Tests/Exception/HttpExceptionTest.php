<?php

namespace Symfony\Component\HttpKernel\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpExceptionTest extends TestCase
{
    public function headerDataProvider()
    {
        return [
            [['X-Test' => 'Test']],
            [['X-Test' => 1]],
            [
                [
                    ['X-Test' => 'Test'],
                    ['X-Test-2' => 'Test-2'],
                ],
            ],
        ];
    }

    public function testHeadersDefault(): void
    {
        $exception = $this->createException();
        $this->assertSame([], $exception->getHeaders());
    }

    /**
     * @dataProvider headerDataProvider
     */
    public function testHeadersConstructor(array $headers): void
    {
        $exception = new HttpException(200, null, null, $headers);
        $this->assertSame($headers, $exception->getHeaders());
    }

    /**
     * @dataProvider headerDataProvider
     */
    public function testHeadersSetter(array $headers): void
    {
        $exception = $this->createException();
        $exception->setHeaders($headers);
        $this->assertSame($headers, $exception->getHeaders());
    }

    protected function createException()
    {
        return new HttpException(200);
    }
}
