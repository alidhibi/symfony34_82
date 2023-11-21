<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\Fragment;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\SsiFragmentRenderer;
use Symfony\Component\HttpKernel\HttpCache\Ssi;
use Symfony\Component\HttpKernel\UriSigner;

class SsiFragmentRendererTest extends TestCase
{
    public function testRenderFallbackToInlineStrategyIfSsiNotSupported(): void
    {
        $strategy = new SsiFragmentRenderer($this->getInlineStrategy(true), new Ssi());
        $strategy->render('/', Request::create('/'));
    }

    public function testRender(): void
    {
        $strategy = new SsiFragmentRenderer($this->getInlineStrategy(), new Ssi());

        $request = Request::create('/');
        $request->setLocale('fr');

        $request->headers->set('Surrogate-Capability', 'SSI/1.0');

        $this->assertEquals('<!--#include virtual="/" -->', $strategy->render('/', $request)->getContent());
        $this->assertEquals('<!--#include virtual="/" -->', $strategy->render('/', $request, ['comment' => 'This is a comment'])->getContent(), 'Strategy options should not impact the ssi include tag');
    }

    public function testRenderControllerReference(): void
    {
        $signer = new UriSigner('foo');
        $strategy = new SsiFragmentRenderer($this->getInlineStrategy(), new Ssi(), $signer);

        $request = Request::create('/');
        $request->setLocale('fr');

        $request->headers->set('Surrogate-Capability', 'SSI/1.0');

        $reference = new ControllerReference('main_controller', [], []);
        $altReference = new ControllerReference('alt_controller', [], []);

        $this->assertEquals(
            '<!--#include virtual="/_fragment?_hash=Jz1P8NErmhKTeI6onI1EdAXTB85359MY3RIk5mSJ60w%3D&_path=_format%3Dhtml%26_locale%3Dfr%26_controller%3Dmain_controller" -->',
            $strategy->render($reference, $request, ['alt' => $altReference])->getContent()
        );
    }

    public function testRenderControllerReferenceWithoutSignerThrowsException(): void
    {
        $this->expectException('LogicException');
        $strategy = new SsiFragmentRenderer($this->getInlineStrategy(), new Ssi());

        $request = Request::create('/');
        $request->setLocale('fr');

        $request->headers->set('Surrogate-Capability', 'SSI/1.0');

        $strategy->render(new ControllerReference('main_controller'), $request);
    }

    public function testRenderAltControllerReferenceWithoutSignerThrowsException(): void
    {
        $this->expectException('LogicException');
        $strategy = new SsiFragmentRenderer($this->getInlineStrategy(), new Ssi());

        $request = Request::create('/');
        $request->setLocale('fr');

        $request->headers->set('Surrogate-Capability', 'SSI/1.0');

        $strategy->render('/', $request, ['alt' => new ControllerReference('alt_controller')]);
    }

    private function getInlineStrategy(bool $called = false)
    {
        $inline = $this->getMockBuilder(\Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer::class)->disableOriginalConstructor()->getMock();

        if ($called) {
            $inline->expects($this->once())->method('render');
        }

        return $inline;
    }
}