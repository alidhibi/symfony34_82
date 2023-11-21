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

use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Csrf\CsrfToken;

abstract class AbstractTableLayoutTest extends AbstractLayoutTest
{
    public function testRow(): void
    {
        $form = $this->factory->createNamed('name', \Symfony\Component\Form\Extension\Core\Type\TextType::class);
        $form->addError(new FormError('[trans]Error![/trans]'));

        $view = $form->createView();
        $html = $this->renderRow($view);

        $this->assertMatchesXpath($html,
'/tr
    [
        ./td
            [./label[@for="name"]]
        /following-sibling::td
            [
                ./ul
                    [./li[.="[trans]Error![/trans]"]]
                    [count(./li)=1]
                /following-sibling::input[@id="name"]
            ]
    ]
'
        );
    }

    public function testLabelIsNotRenderedWhenSetToFalse(): void
    {
        $form = $this->factory->createNamed('name', \Symfony\Component\Form\Extension\Core\Type\TextType::class, null, [
            'label' => false,
        ]);
        $html = $this->renderRow($form->createView());

        $this->assertMatchesXpath($html,
'/tr
    [
        ./td
            [count(//label)=0]
        /following-sibling::td
            [./input[@id="name"]]
    ]
'
        );
    }

    public function testRepeatedRow(): void
    {
        $form = $this->factory->createNamed('name', \Symfony\Component\Form\Extension\Core\Type\RepeatedType::class);
        $html = $this->renderRow($form->createView());

        $this->assertMatchesXpath($html,
'/tr
    [
        ./td
            [./label[@for="name_first"]]
        /following-sibling::td
            [./input[@id="name_first"]]
    ]
/following-sibling::tr
    [
        ./td
            [./label[@for="name_second"]]
        /following-sibling::td
            [./input[@id="name_second"]]
    ]
/following-sibling::tr[@style="display: none"]
    [./td[@colspan="2"]/input
        [@type="hidden"]
        [@id="name__token"]
    ]
    [count(../tr)=3]
'
        );
    }

    public function testRepeatedRowWithErrors(): void
    {
        $form = $this->factory->createNamed('name', \Symfony\Component\Form\Extension\Core\Type\RepeatedType::class);
        $form->addError(new FormError('[trans]Error![/trans]'));

        $view = $form->createView();
        $html = $this->renderRow($view);

        // The errors of the form are not rendered by intention!
        // In practice, repeated fields cannot have errors as all errors
        // on them are mapped to the first child.
        // (see RepeatedTypeValidatorExtension)

        $this->assertMatchesXpath($html,
'/tr
    [
        ./td
            [./label[@for="name_first"]]
        /following-sibling::td
            [./input[@id="name_first"]]
    ]
/following-sibling::tr
    [
        ./td
            [./label[@for="name_second"]]
        /following-sibling::td
            [./input[@id="name_second"]]
    ]
/following-sibling::tr[@style="display: none"]
    [./td[@colspan="2"]/input
        [@type="hidden"]
        [@id="name__token"]
    ]
    [count(../tr)=3]
'
        );
    }

    public function testButtonRow(): void
    {
        $form = $this->factory->createNamed('name', \Symfony\Component\Form\Extension\Core\Type\ButtonType::class);
        $view = $form->createView();
        $html = $this->renderRow($view);

        $this->assertMatchesXpath($html,
'/tr
    [
        ./td
            [.=""]
        /following-sibling::td
            [./button[@type="button"][@name="name"]]
    ]
    [count(//label)=0]
'
        );
    }

    public function testRest(): void
    {
        $view = $this->factory->createNamedBuilder('name', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
            ->add('field1', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('field2', \Symfony\Component\Form\Extension\Core\Type\RepeatedType::class)
            ->add('field3', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('field4', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->getForm()
            ->createView();

        // Render field2 row -> does not implicitly call renderWidget because
        // it is a repeated field!
        $this->renderRow($view['field2']);

        // Render field3 widget
        $this->renderWidget($view['field3']);

        // Rest should only contain field1 and field4
        $html = $this->renderRest($view);

        $this->assertMatchesXpath($html,
'/tr
    [
        ./td
            [./label[@for="name_field1"]]
        /following-sibling::td
            [./input[@id="name_field1"]]
    ]
/following-sibling::tr
    [
        ./td
            [./label[@for="name_field4"]]
        /following-sibling::td
            [./input[@id="name_field4"]]
    ]
    [count(../tr)=3]
    [count(..//label)=2]
    [count(..//input)=3]
/following-sibling::tr[@style="display: none"]
    [./td[@colspan="2"]/input
        [@type="hidden"]
        [@id="name__token"]
    ]
'
        );
    }

    public function testCollection(): void
    {
        $form = $this->factory->createNamed('names', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, ['a', 'b'], [
            'entry_type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
        ]);

        $this->assertWidgetMatchesXpath($form->createView(), [],
'/table
    [
        ./tr[./td/input[@type="text"][@value="a"]]
        /following-sibling::tr[./td/input[@type="text"][@value="b"]]
        /following-sibling::tr[@style="display: none"][./td[@colspan="2"]/input[@type="hidden"][@id="names__token"]]
    ]
    [count(./tr[./td/input])=3]
'
        );
    }

    public function testEmptyCollection(): void
    {
        $form = $this->factory->createNamed('names', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [], [
            'entry_type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
        ]);

        $this->assertWidgetMatchesXpath($form->createView(), [],
'/table
    [./tr[@style="display: none"][./td[@colspan="2"]/input[@type="hidden"][@id="names__token"]]]
    [count(./tr[./td/input])=1]
'
        );
    }

    public function testForm(): void
    {
        $view = $this->factory->createNamedBuilder('name', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
            ->setMethod('PUT')
            ->setAction('http://example.com')
            ->add('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('lastName', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->getForm()
            ->createView();

        $html = $this->renderForm($view, [
            'id' => 'my&id',
            'attr' => ['class' => 'my&class'],
        ]);

        $this->assertMatchesXpath($html,
'/form
    [
        ./input[@type="hidden"][@name="_method"][@value="PUT"]
        /following-sibling::table
            [
                ./tr
                    [
                        ./td
                            [./label[@for="name_firstName"]]
                        /following-sibling::td
                            [./input[@id="name_firstName"]]
                    ]
                /following-sibling::tr
                    [
                        ./td
                            [./label[@for="name_lastName"]]
                        /following-sibling::td
                            [./input[@id="name_lastName"]]
                    ]
                /following-sibling::tr[@style="display: none"]
                    [./td[@colspan="2"]/input
                        [@type="hidden"]
                        [@id="name__token"]
                    ]
            ]
            [count(.//input)=3]
            [@id="my&id"]
            [@class="my&class"]
    ]
    [@method="post"]
    [@action="http://example.com"]
    [@class="my&class"]
'
        );
    }

    public function testFormWidget(): void
    {
        $view = $this->factory->createNamedBuilder('name', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
            ->add('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('lastName', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->getForm()
            ->createView();

        $this->assertWidgetMatchesXpath($view, [],
'/table
    [
        ./tr
            [
                ./td
                    [./label[@for="name_firstName"]]
                /following-sibling::td
                    [./input[@id="name_firstName"]]
            ]
        /following-sibling::tr
            [
                ./td
                    [./label[@for="name_lastName"]]
                /following-sibling::td
                    [./input[@id="name_lastName"]]
            ]
        /following-sibling::tr[@style="display: none"]
            [./td[@colspan="2"]/input
                [@type="hidden"]
                [@id="name__token"]
            ]
    ]
    [count(.//input)=3]
'
        );
    }

    // https://github.com/symfony/symfony/issues/2308
    public function testNestedFormError(): void
    {
        $form = $this->factory->createNamedBuilder('name', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
            ->add($this->factory
                ->createNamedBuilder('child', \Symfony\Component\Form\Extension\Core\Type\FormType::class, null, ['error_bubbling' => false])
                ->add('grandChild', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
            )
            ->getForm();

        $form->get('child')->addError(new FormError('[trans]Error![/trans]'));

        $this->assertWidgetMatchesXpath($form->createView(), [],
'/table
    [
        ./tr/td/ul[./li[.="[trans]Error![/trans]"]]
        /following-sibling::table[@id="name_child"]
    ]
    [count(.//li[.="[trans]Error![/trans]"])=1]
'
        );
    }

    public function testCsrf(): void
    {
        $this->csrfTokenManager->expects($this->any())
            ->method('getToken')
            ->willReturn(new CsrfToken('token_id', 'foo&bar'));

        $form = $this->factory->createNamedBuilder('name', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
            ->add($this->factory
                // No CSRF protection on nested forms
                ->createNamedBuilder('child', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
                ->add($this->factory->createNamedBuilder('grandchild', \Symfony\Component\Form\Extension\Core\Type\TextType::class))
            )
            ->getForm();

        $this->assertWidgetMatchesXpath($form->createView(), [],
'/table
    [
        ./tr[@style="display: none"]
            [./td[@colspan="2"]/input
                [@type="hidden"]
                [@id="name__token"]
            ]
    ]
    [count(.//input[@type="hidden"])=1]
'
        );
    }

    public function testRepeated(): void
    {
        $form = $this->factory->createNamed('name', \Symfony\Component\Form\Extension\Core\Type\RepeatedType::class, 'foobar', [
            'type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
        ]);

        $this->assertWidgetMatchesXpath($form->createView(), [],
'/table
    [
        ./tr
            [
                ./td
                    [./label[@for="name_first"]]
                /following-sibling::td
                    [./input[@type="text"][@id="name_first"]]
            ]
        /following-sibling::tr
            [
                ./td
                    [./label[@for="name_second"]]
                /following-sibling::td
                    [./input[@type="text"][@id="name_second"]]
            ]
        /following-sibling::tr[@style="display: none"]
            [./td[@colspan="2"]/input
                [@type="hidden"]
                [@id="name__token"]
            ]
    ]
    [count(.//input)=3]
'
        );
    }

    public function testRepeatedWithCustomOptions(): void
    {
        $form = $this->factory->createNamed('name', \Symfony\Component\Form\Extension\Core\Type\RepeatedType::class, 'foobar', [
            'type' => \Symfony\Component\Form\Extension\Core\Type\PasswordType::class,
            'first_options' => ['label' => 'Test', 'required' => false],
            'second_options' => ['label' => 'Test2'],
        ]);

        $this->assertWidgetMatchesXpath($form->createView(), [],
'/table
    [
        ./tr
            [
                ./td
                    [./label[@for="name_first"][.="[trans]Test[/trans]"]]
                /following-sibling::td
                    [./input[@type="password"][@id="name_first"][@required="required"]]
            ]
        /following-sibling::tr
            [
                ./td
                    [./label[@for="name_second"][.="[trans]Test2[/trans]"]]
                /following-sibling::td
                    [./input[@type="password"][@id="name_second"][@required="required"]]
            ]
        /following-sibling::tr[@style="display: none"]
            [./td[@colspan="2"]/input
                [@type="hidden"]
                [@id="name__token"]
            ]
    ]
    [count(.//input)=3]
'
        );
    }

    /**
     * The block "_name_child_label" should be overridden in the theme of the
     * implemented driver.
     */
    public function testCollectionRowWithCustomBlock(): void
    {
        $collection = ['one', 'two', 'three'];
        $form = $this->factory->createNamedBuilder('names', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, $collection)
            ->getForm();

        $this->assertWidgetMatchesXpath($form->createView(), [],
'/table
    [
        ./tr[./td/label[.="Custom label: [trans]0[/trans]"]]
        /following-sibling::tr[./td/label[.="Custom label: [trans]1[/trans]"]]
        /following-sibling::tr[./td/label[.="Custom label: [trans]2[/trans]"]]
    ]
'
        );
    }

    public function testFormEndWithRest(): void
    {
        $view = $this->factory->createNamedBuilder('name', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
            ->add('field1', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('field2', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->getForm()
            ->createView();

        $this->renderWidget($view['field1']);

        // Rest should only contain field2
        $html = $this->renderEnd($view);

        // Insert the start tag, the end tag should be rendered by the helper
        // Unfortunately this is not valid HTML, because the surrounding table
        // tag is missing. If someone renders a form with table layout
        // manually, they should call form_rest() explicitly within the <table>
        // tag.
        $this->assertMatchesXpath('<form>'.$html,
'/form
    [
        ./tr
            [
                ./td
                    [./label[@for="name_field2"]]
                /following-sibling::td
                    [./input[@id="name_field2"]]
            ]
        /following-sibling::tr[@style="display: none"]
            [./td[@colspan="2"]/input
                [@type="hidden"]
                [@id="name__token"]
            ]
    ]
'
        );
    }

    public function testFormEndWithoutRest(): void
    {
        $view = $this->factory->createNamedBuilder('name', \Symfony\Component\Form\Extension\Core\Type\FormType::class)
            ->add('field1', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('field2', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->getForm()
            ->createView();

        $this->renderWidget($view['field1']);

        // Rest should only contain field2, but isn't rendered
        $html = $this->renderEnd($view, ['render_rest' => false]);

        $this->assertEquals('</form>', $html);
    }

    public function testWidgetContainerAttributes(): void
    {
        $form = $this->factory->createNamed('form', \Symfony\Component\Form\Extension\Core\Type\FormType::class, null, [
            'attr' => ['class' => 'foobar', 'data-foo' => 'bar'],
        ]);

        $form->add('text', \Symfony\Component\Form\Extension\Core\Type\TextType::class);

        $html = $this->renderWidget($form->createView());

        // compare plain HTML to check the whitespace
        $this->assertStringContainsString('<table id="form" class="foobar" data-foo="bar">', $html);
    }

    public function testWidgetContainerAttributeNameRepeatedIfTrue(): void
    {
        $form = $this->factory->createNamed('form', \Symfony\Component\Form\Extension\Core\Type\FormType::class, null, [
            'attr' => ['foo' => true],
        ]);

        $html = $this->renderWidget($form->createView());

        // foo="foo"
        $this->assertStringContainsString('<table id="form" foo="foo">', $html);
    }
}