<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\DataCollector\Type;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\DataCollector\Type\DataCollectorTypeExtension;

class DataCollectorTypeExtensionTest extends TestCase
{
    private \Symfony\Component\Form\Extension\DataCollector\Type\DataCollectorTypeExtension $extension;

    /**
     * @var MockObject
     */
    private $dataCollector;

    protected function setUp()
    {
        $this->dataCollector = $this->getMockBuilder(\Symfony\Component\Form\Extension\DataCollector\FormDataCollectorInterface::class)->getMock();
        $this->extension = new DataCollectorTypeExtension($this->dataCollector);
    }

    public function testGetExtendedType(): void
    {
        $this->assertEquals(\Symfony\Component\Form\Extension\Core\Type\FormType::class, $this->extension->getExtendedType());
    }

    public function testBuildForm(): void
    {
        $builder = $this->getMockBuilder(\Symfony\Component\Form\Test\FormBuilderInterface::class)->getMock();
        $builder->expects($this->atLeastOnce())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf(\Symfony\Component\Form\Extension\DataCollector\EventListener\DataCollectorListener::class));

        $this->extension->buildForm($builder, []);
    }
}
