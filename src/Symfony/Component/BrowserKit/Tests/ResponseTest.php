<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\BrowserKit\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\Response;

class ResponseTest extends TestCase
{
    public function testGetUri(): void
    {
        $response = new Response('foo');
        $this->assertEquals('foo', $response->getContent(), '->getContent() returns the content of the response');
    }

    public function testGetStatus(): void
    {
        $response = new Response('foo', 304);
        $this->assertEquals('304', $response->getStatus(), '->getStatus() returns the status of the response');
    }

    public function testGetHeaders(): void
    {
        $response = new Response('foo', 200, ['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $response->getHeaders(), '->getHeaders() returns the headers of the response');
    }

    public function testGetHeader(): void
    {
        $response = new Response('foo', 200, [
            'Content-Type' => 'text/html',
            'Set-Cookie' => ['foo=bar', 'bar=foo'],
        ]);

        $this->assertEquals('text/html', $response->getHeader('Content-Type'), '->getHeader() returns a header of the response');
        $this->assertEquals('text/html', $response->getHeader('content-type'), '->getHeader() returns a header of the response');
        $this->assertEquals('text/html', $response->getHeader('content_type'), '->getHeader() returns a header of the response');
        $this->assertEquals('foo=bar', $response->getHeader('Set-Cookie'), '->getHeader() returns the first header value');
        $this->assertEquals(['foo=bar', 'bar=foo'], $response->getHeader('Set-Cookie', false), '->getHeader() returns all header values if first is false');

        $this->assertNull($response->getHeader('foo'), '->getHeader() returns null if the header is not defined');
        $this->assertEquals([], $response->getHeader('foo', false), '->getHeader() returns an empty array if the header is not defined and first is set to false');
    }

    public function testMagicToString(): void
    {
        $response = new Response('foo', 304, ['foo' => 'bar']);

        $this->assertEquals("foo: bar\n\nfoo", $response->__toString(), '->__toString() returns the headers and the content as a string');
    }

    public function testMagicToStringWithMultipleSetCookieHeader(): void
    {
        $headers = [
            'content-type' => 'text/html; charset=utf-8',
            'set-cookie' => ['foo=bar', 'bar=foo'],
        ];

        $expected = 'content-type: text/html; charset=utf-8'."\n";
        $expected .= 'set-cookie: foo=bar'."\n";
        $expected .= 'set-cookie: bar=foo

';
        $expected .= 'foo';

        $response = new Response('foo', 304, $headers);

        $this->assertEquals($expected, $response->__toString(), '->__toString() returns the headers and the content as a string');
    }
}
