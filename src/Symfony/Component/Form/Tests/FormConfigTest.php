<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigBuilder;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormConfigTest extends TestCase
{
    public function getHtml4Ids(): array
    {
        return [
            ['z0'],
            ['A0'],
            ['A9'],
            ['Z0'],
            ['#', \Symfony\Component\Form\Exception\InvalidArgumentException::class],
            ['a#', \Symfony\Component\Form\Exception\InvalidArgumentException::class],
            ['a$', \Symfony\Component\Form\Exception\InvalidArgumentException::class],
            ['a%', \Symfony\Component\Form\Exception\InvalidArgumentException::class],
            ['a ', \Symfony\Component\Form\Exception\InvalidArgumentException::class],
            ["a\t", \Symfony\Component\Form\Exception\InvalidArgumentException::class],
            ["a\n", \Symfony\Component\Form\Exception\InvalidArgumentException::class],
            ['a-'],
            ['a_'],
            ['a:'],
            // Periods are allowed by the HTML4 spec, but disallowed by us
            // because they break the generated property paths
            ['a.', \Symfony\Component\Form\Exception\InvalidArgumentException::class],
            // Contrary to the HTML4 spec, we allow names starting with a
            // number, otherwise naming fields by collection indices is not
            // possible.
            // For root forms, leading digits will be stripped from the
            // "id" attribute to produce valid HTML4.
            ['0'],
            ['9'],
            // Contrary to the HTML4 spec, we allow names starting with an
            // underscore, since this is already a widely used practice in
            // Symfony.
            // For root forms, leading underscores will be stripped from the
            // "id" attribute to produce valid HTML4.
            ['_'],
            // Integers are allowed
            [0],
            [123],
            // NULL is allowed
            [null],
            // Other types are not
            [1.23, \Symfony\Component\Form\Exception\UnexpectedTypeException::class],
            [5., \Symfony\Component\Form\Exception\UnexpectedTypeException::class],
            [true, \Symfony\Component\Form\Exception\UnexpectedTypeException::class],
            [new \stdClass(), \Symfony\Component\Form\Exception\UnexpectedTypeException::class],
        ];
    }

    /**
     * @dataProvider getHtml4Ids
     */
    public function testNameAcceptsOnlyNamesValidAsIdsInHtml4(string|int|float|bool|\stdClass|null $name, string $expectedException = null): void
    {
        $dispatcher = $this->getMockBuilder(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class)->getMock();

        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $formConfigBuilder = new FormConfigBuilder($name, null, $dispatcher);

        $this->assertSame((string) $name, $formConfigBuilder->getName());
    }

    public function testGetRequestHandlerCreatesNativeRequestHandlerIfNotSet(): void
    {
        $config = $this->getConfigBuilder()->getFormConfig();

        $this->assertInstanceOf(\Symfony\Component\Form\NativeRequestHandler::class, $config->getRequestHandler());
    }

    public function testGetRequestHandlerReusesNativeRequestHandlerInstance(): void
    {
        $config1 = $this->getConfigBuilder()->getFormConfig();
        $config2 = $this->getConfigBuilder()->getFormConfig();

        $this->assertSame($config1->getRequestHandler(), $config2->getRequestHandler());
    }

    public function testSetMethodAllowsGet(): void
    {
        $formConfigBuilder = $this->getConfigBuilder();
        $formConfigBuilder->setMethod('GET');

        self::assertSame('GET', $formConfigBuilder->getMethod());
    }

    public function testSetMethodAllowsPost(): void
    {
        $formConfigBuilder = $this->getConfigBuilder();
        $formConfigBuilder->setMethod('POST');

        self::assertSame('POST', $formConfigBuilder->getMethod());
    }

    public function testSetMethodAllowsPut(): void
    {
        $formConfigBuilder = $this->getConfigBuilder();
        $formConfigBuilder->setMethod('PUT');

        self::assertSame('PUT', $formConfigBuilder->getMethod());
    }

    public function testSetMethodAllowsDelete(): void
    {
        $formConfigBuilder = $this->getConfigBuilder();
        $formConfigBuilder->setMethod('DELETE');

        self::assertSame('DELETE', $formConfigBuilder->getMethod());
    }

    public function testSetMethodAllowsPatch(): void
    {
        $formConfigBuilder = $this->getConfigBuilder();
        $formConfigBuilder->setMethod('PATCH');

        self::assertSame('PATCH', $formConfigBuilder->getMethod());
    }

    public function testSetMethodDoesNotAllowOtherValues(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\InvalidArgumentException::class);
        $this->getConfigBuilder()->setMethod('foo');
    }

    private function getConfigBuilder($name = 'name'): \Symfony\Component\Form\FormConfigBuilder
    {
        $dispatcher = $this->getMockBuilder(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class)->getMock();

        return new FormConfigBuilder($name, null, $dispatcher);
    }
}
