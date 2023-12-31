<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Templating\Loader;

use Symfony\Bundle\FrameworkBundle\Templating\Loader\TemplateLocator;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class TemplateLocatorTest extends TestCase
{
    public function testLocateATemplate(): void
    {
        $template = new TemplateReference('bundle', 'controller', 'name', 'format', 'engine');

        $fileLocator = $this->getFileLocator();

        $fileLocator
            ->expects($this->once())
            ->method('locate')
            ->with($template->getPath())
            ->willReturn('/path/to/template')
        ;

        $locator = new TemplateLocator($fileLocator);

        $this->assertEquals('/path/to/template', $locator->locate($template));

        // Assert cache is used as $fileLocator->locate should be called only once
        $this->assertEquals('/path/to/template', $locator->locate($template));
    }

    public function testLocateATemplateFromCacheDir(): void
    {
        $template = new TemplateReference('bundle', 'controller', 'name', 'format', 'engine');

        $fileLocator = $this->getFileLocator();

        $locator = new TemplateLocator($fileLocator, __DIR__.'/../../Fixtures');

        $this->assertEquals(realpath(__DIR__.'/../../Fixtures/Resources/views/this.is.a.template.format.engine'), $locator->locate($template));
    }

    public function testThrowsExceptionWhenTemplateNotFound(): void
    {
        $template = new TemplateReference('bundle', 'controller', 'name', 'format', 'engine');

        $fileLocator = $this->getFileLocator();

        $errorMessage = 'FileLocator exception message';

        $fileLocator
            ->expects($this->once())
            ->method('locate')
            ->willThrowException(new \InvalidArgumentException($errorMessage))
        ;

        $locator = new TemplateLocator($fileLocator);

        try {
            $locator->locate($template);
            $this->fail('->locate() should throw an exception when the file is not found.');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertStringContainsString(
                $errorMessage,
                $invalidArgumentException->getMessage(),
                'TemplateLocator exception should propagate the FileLocator exception message'
            );
        }
    }

    public function testThrowsAnExceptionWhenTemplateIsNotATemplateReferenceInterface(): void
    {
        $this->expectException('InvalidArgumentException');
        $locator = new TemplateLocator($this->getFileLocator());
        $locator->locate('template');
    }

    protected function getFileLocator()
    {
        return $this
            ->getMockBuilder('Symfony\Component\Config\FileLocator')
            ->setMethods(['locate'])
            ->setConstructorArgs(['/path/to/fallback'])
            ->getMock()
        ;
    }
}
