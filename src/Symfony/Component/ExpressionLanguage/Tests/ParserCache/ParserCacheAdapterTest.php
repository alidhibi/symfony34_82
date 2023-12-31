<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ExpressionLanguage\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheAdapter;

/**
 * @group legacy
 */
class ParserCacheAdapterTest extends TestCase
{
    public function testGetItem(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();

        $key = 'key';
        $value = 'value';
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);

        $poolMock
            ->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($value)
        ;

        $cacheItem = $parserCacheAdapter->getItem($key);

        $this->assertEquals($value, $cacheItem->get());
        $this->assertTrue($cacheItem->isHit());
    }

    public function testSave(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();
        $cacheItemMock = $this->getMockBuilder('Psr\Cache\CacheItemInterface')->getMock();
        $key = 'key';
        $value = new ParsedExpression('1 + 1', new Node([], []));
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);

        $poolMock
            ->expects($this->once())
            ->method('save')
            ->with($key, $value)
        ;

        $cacheItemMock
            ->expects($this->once())
            ->method('getKey')
            ->willReturn($key)
        ;

        $cacheItemMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($value)
        ;

        $parserCacheAdapter->save($cacheItemMock);
    }

    public function testGetItems(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);
        $this->expectException(\BadMethodCallException::class);

        $parserCacheAdapter->getItems();
    }

    public function testHasItem(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();
        $key = 'key';
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);
        $this->expectException(\BadMethodCallException::class);

        $parserCacheAdapter->hasItem($key);
    }

    public function testClear(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);
        $this->expectException(\BadMethodCallException::class);

        $parserCacheAdapter->clear();
    }

    public function testDeleteItem(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();
        $key = 'key';
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);
        $this->expectException(\BadMethodCallException::class);

        $parserCacheAdapter->deleteItem($key);
    }

    public function testDeleteItems(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();
        $keys = ['key'];
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);
        $this->expectException(\BadMethodCallException::class);

        $parserCacheAdapter->deleteItems($keys);
    }

    public function testSaveDeferred(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);
        $cacheItemMock = $this->getMockBuilder('Psr\Cache\CacheItemInterface')->getMock();
        $this->expectException(\BadMethodCallException::class);

        $parserCacheAdapter->saveDeferred($cacheItemMock);
    }

    public function testCommit(): void
    {
        $poolMock = $this->getMockBuilder(\Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface::class)->getMock();
        $parserCacheAdapter = new ParserCacheAdapter($poolMock);
        $this->expectException(\BadMethodCallException::class);

        $parserCacheAdapter->commit();
    }
}
