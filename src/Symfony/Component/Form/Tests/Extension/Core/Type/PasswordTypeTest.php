<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\Type;

class PasswordTypeTest extends BaseTypeTest
{
    final const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\PasswordType';

    public function testEmptyIfNotSubmitted(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE);
        $form->setData('pAs5w0rd');

        $this->assertSame('', $form->createView()->vars['value']);
    }

    public function testEmptyIfSubmitted(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE);
        $form->submit('pAs5w0rd');

        $this->assertSame('', $form->createView()->vars['value']);
    }

    public function testNotEmptyIfSubmittedAndNotAlwaysEmpty(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['always_empty' => false]);
        $form->submit('pAs5w0rd');

        $this->assertSame('pAs5w0rd', $form->createView()->vars['value']);
    }

    public function testNotTrimmed(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null);
        $form->submit(' pAs5w0rd ');

        $this->assertSame(' pAs5w0rd ', $form->getData());
    }

    public function testSubmitNull($expected = null, $norm = null, $view = null): void
    {
        parent::testSubmitNull($expected, $norm, '');
    }
}
