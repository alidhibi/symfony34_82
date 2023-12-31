<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\ChoiceList;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\LazyChoiceList;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyChoiceListTest extends TestCase
{
    private \Symfony\Component\Form\ChoiceList\LazyChoiceList $list;

    /**
     * @var MockObject
     */
    private $loadedList;

    /**
     * @var MockObject
     */
    private $loader;

    private \Closure $value;

    protected function setUp()
    {
        $this->loadedList = $this->getMockBuilder(\Symfony\Component\Form\ChoiceList\ChoiceListInterface::class)->getMock();
        $this->loader = $this->getMockBuilder(\Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface::class)->getMock();
        $this->value = static function () : void {
        };
        $this->list = new LazyChoiceList($this->loader, $this->value);
    }

    public function testGetChoiceLoadersLoadsLoadedListOnFirstCall(): void
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturn($this->loadedList);

        // The same list is returned by the loader
        $this->loadedList->expects($this->exactly(2))
            ->method('getChoices')
            ->willReturn(['RESULT']);

        $this->assertSame(['RESULT'], $this->list->getChoices());
        $this->assertSame(['RESULT'], $this->list->getChoices());
    }

    /**
     * @group legacy
     */
    public function testGetChoicesUsesLoadedListWhenLoaderDoesNotCacheChoiceListOnFirstCall(): void
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturnOnConsecutiveCalls($this->loadedList, new ArrayChoiceList(['a', 'b']));

        // The same list is returned by the lazy choice list
        $this->loadedList->expects($this->exactly(2))
            ->method('getChoices')
            ->willReturn('RESULT');

        $this->assertSame('RESULT', $this->list->getChoices());
        $this->assertSame('RESULT', $this->list->getChoices());
    }

    public function testGetValuesLoadsLoadedListOnFirstCall(): void
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturn($this->loadedList);

        // The same list is returned by the loader
        $this->loadedList->expects($this->exactly(2))
            ->method('getValues')
            ->willReturn(['RESULT']);

        $this->assertSame(['RESULT'], $this->list->getValues());
        $this->assertSame(['RESULT'], $this->list->getValues());
    }

    public function testGetStructuredValuesLoadsLoadedListOnFirstCall(): void
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturn($this->loadedList);

        // The same list is returned by the loader
        $this->loadedList->expects($this->exactly(2))
            ->method('getStructuredValues')
            ->willReturn(['RESULT']);

        $this->assertSame(['RESULT'], $this->list->getStructuredValues());
        $this->assertSame(['RESULT'], $this->list->getStructuredValues());
    }

    public function testGetOriginalKeysLoadsLoadedListOnFirstCall(): void
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturn($this->loadedList);

        // The same list is returned by the loader
        $this->loadedList->expects($this->exactly(2))
            ->method('getOriginalKeys')
            ->willReturn(['RESULT']);

        $this->assertSame(['RESULT'], $this->list->getOriginalKeys());
        $this->assertSame(['RESULT'], $this->list->getOriginalKeys());
    }

    public function testGetChoicesForValuesForwardsCallIfListNotLoaded(): void
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoicesForValues')
            ->with(['a', 'b'])
            ->willReturn(['RESULT']);

        $this->assertSame(['RESULT'], $this->list->getChoicesForValues(['a', 'b']));
        $this->assertSame(['RESULT'], $this->list->getChoicesForValues(['a', 'b']));
    }

    public function testGetChoicesForValuesUsesLoadedList(): void
    {
        $this->loader->expects($this->exactly(3))
            ->method('loadChoiceList')
            ->with($this->value)
            // For BC, the same choice loaded list is returned 3 times
            // It should only twice in 4.0
            ->willReturn($this->loadedList);

        $this->loader->expects($this->never())
            ->method('loadChoicesForValues');

        $this->loadedList->expects($this->exactly(2))
            ->method('getChoicesForValues')
            ->with(['a', 'b'])
            ->willReturn(['RESULT']);

        // load choice list
        $this->list->getChoices();

        $this->assertSame(['RESULT'], $this->list->getChoicesForValues(['a', 'b']));
        $this->assertSame(['RESULT'], $this->list->getChoicesForValues(['a', 'b']));
    }

    /**
     * @group legacy
     */
    public function testGetValuesForChoicesForwardsCallIfListNotLoaded(): void
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadValuesForChoices')
            ->with(['a', 'b'])
            ->willReturn('RESULT');

        $this->assertSame('RESULT', $this->list->getValuesForChoices(['a', 'b']));
        $this->assertSame('RESULT', $this->list->getValuesForChoices(['a', 'b']));
    }

    public function testGetValuesForChoicesUsesLoadedList(): void
    {
        $this->loader->expects($this->exactly(3))
            ->method('loadChoiceList')
            ->with($this->value)
            // For BC, the same choice loaded list is returned 3 times
            // It should only twice in 4.0
            ->willReturn($this->loadedList);

        $this->loader->expects($this->never())
            ->method('loadValuesForChoices');

        $this->loadedList->expects($this->exactly(2))
            ->method('getValuesForChoices')
            ->with(['a', 'b'])
            ->willReturn(['RESULT']);

        // load choice list
        $this->list->getChoices();

        $this->assertSame(['RESULT'], $this->list->getValuesForChoices(['a', 'b']));
        $this->assertSame(['RESULT'], $this->list->getValuesForChoices(['a', 'b']));
    }
}
