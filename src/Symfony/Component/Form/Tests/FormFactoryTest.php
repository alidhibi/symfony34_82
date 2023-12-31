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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormTypeGuesserChain;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormFactoryTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $guesser1;

    /**
     * @var MockObject
     */
    private $guesser2;

    /**
     * @var MockObject
     */
    private $registry;

    /**
     * @var MockObject
     */
    private $builder;

    private \Symfony\Component\Form\FormFactory $factory;

    protected function setUp()
    {
        $this->guesser1 = $this->getMockBuilder(\Symfony\Component\Form\FormTypeGuesserInterface::class)->getMock();
        $this->guesser2 = $this->getMockBuilder(\Symfony\Component\Form\FormTypeGuesserInterface::class)->getMock();
        $this->registry = $this->getMockBuilder(\Symfony\Component\Form\FormRegistryInterface::class)->getMock();
        $this->builder = $this->getMockBuilder(\Symfony\Component\Form\Test\FormBuilderInterface::class)->getMock();
        $this->factory = new FormFactory($this->registry);

        $this->registry->expects($this->any())
            ->method('getTypeGuesser')
            ->willReturn(new FormTypeGuesserChain([
                $this->guesser1,
                $this->guesser2,
            ]));
    }

    public function testCreateNamedBuilderWithTypeName(): void
    {
        $options = ['a' => '1', 'b' => '2'];
        $resolvedOptions = ['a' => '2', 'b' => '3'];
        $resolvedType = $this->getMockResolvedType();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with('type')
            ->willReturn($resolvedType);

        $resolvedType->expects($this->once())
            ->method('createBuilder')
            ->with($this->factory, 'name', $options)
            ->willReturn($this->builder);

        $this->builder->expects($this->any())
            ->method('getOptions')
            ->willReturn($resolvedOptions);

        $resolvedType->expects($this->once())
            ->method('buildForm')
            ->with($this->builder, $resolvedOptions);

        $this->assertSame($this->builder, $this->factory->createNamedBuilder('name', 'type', null, $options));
    }

    public function testCreateNamedBuilderFillsDataOption(): void
    {
        $givenOptions = ['a' => '1', 'b' => '2'];
        $expectedOptions = [...$givenOptions, 'data' => 'DATA'];
        $resolvedOptions = ['a' => '2', 'b' => '3', 'data' => 'DATA'];
        $resolvedType = $this->getMockResolvedType();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with('type')
            ->willReturn($resolvedType);

        $resolvedType->expects($this->once())
            ->method('createBuilder')
            ->with($this->factory, 'name', $expectedOptions)
            ->willReturn($this->builder);

        $this->builder->expects($this->any())
            ->method('getOptions')
            ->willReturn($resolvedOptions);

        $resolvedType->expects($this->once())
            ->method('buildForm')
            ->with($this->builder, $resolvedOptions);

        $this->assertSame($this->builder, $this->factory->createNamedBuilder('name', 'type', 'DATA', $givenOptions));
    }

    public function testCreateNamedBuilderDoesNotOverrideExistingDataOption(): void
    {
        $options = ['a' => '1', 'b' => '2', 'data' => 'CUSTOM'];
        $resolvedOptions = ['a' => '2', 'b' => '3', 'data' => 'CUSTOM'];
        $resolvedType = $this->getMockResolvedType();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with('type')
            ->willReturn($resolvedType);

        $resolvedType->expects($this->once())
            ->method('createBuilder')
            ->with($this->factory, 'name', $options)
            ->willReturn($this->builder);

        $this->builder->expects($this->any())
            ->method('getOptions')
            ->willReturn($resolvedOptions);

        $resolvedType->expects($this->once())
            ->method('buildForm')
            ->with($this->builder, $resolvedOptions);

        $this->assertSame($this->builder, $this->factory->createNamedBuilder('name', 'type', 'DATA', $options));
    }

    public function testCreateNamedBuilderThrowsUnderstandableException(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "stdClass" given');
        $this->factory->createNamedBuilder('name', new \stdClass());
    }

    public function testCreateThrowsUnderstandableException(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "stdClass" given');
        $this->factory->create(new \stdClass());
    }

    public function testCreateUsesBlockPrefixIfTypeGivenAsString(): void
    {
        $options = ['a' => '1', 'b' => '2'];
        $resolvedOptions = ['a' => '2', 'b' => '3'];

        // the interface does not have the method, so use the real class
        $resolvedType = $this->getMockBuilder(\Symfony\Component\Form\ResolvedFormType::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolvedType->expects($this->any())
            ->method('getBlockPrefix')
            ->willReturn('TYPE_PREFIX');

        $this->registry->expects($this->any())
            ->method('getType')
            ->with('TYPE')
            ->willReturn($resolvedType);

        $resolvedType->expects($this->once())
            ->method('createBuilder')
            ->with($this->factory, 'TYPE_PREFIX', $options)
            ->willReturn($this->builder);

        $this->builder->expects($this->any())
            ->method('getOptions')
            ->willReturn($resolvedOptions);

        $resolvedType->expects($this->once())
            ->method('buildForm')
            ->with($this->builder, $resolvedOptions);

        $form = $this->createForm();

        $this->builder->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->assertSame($form, $this->factory->create('TYPE', null, $options));
    }

    public function testCreateNamed(): void
    {
        $options = ['a' => '1', 'b' => '2'];
        $resolvedOptions = ['a' => '2', 'b' => '3'];
        $resolvedType = $this->getMockResolvedType();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with('type')
            ->willReturn($resolvedType);

        $resolvedType->expects($this->once())
            ->method('createBuilder')
            ->with($this->factory, 'name', $options)
            ->willReturn($this->builder);

        $this->builder->expects($this->any())
            ->method('getOptions')
            ->willReturn($resolvedOptions);

        $resolvedType->expects($this->once())
            ->method('buildForm')
            ->with($this->builder, $resolvedOptions);

        $form = $this->createForm();

        $this->builder->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->assertSame($form, $this->factory->createNamed('name', 'type', null, $options));
    }

    public function testCreateBuilderForPropertyWithoutTypeGuesser(): void
    {
        $registry = $this->getMockBuilder(\Symfony\Component\Form\FormRegistryInterface::class)->getMock();
        $factory = $this->getMockBuilder(\Symfony\Component\Form\FormFactory::class)
            ->setMethods(['createNamedBuilder'])
            ->setConstructorArgs([$registry])
            ->getMock();

        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class, null, [])
            ->willReturn($this->builder);

        $this->builder = $factory->createBuilderForProperty('Application\Author', 'firstName');

        $this->assertSame($this->builder, $this->builder);
    }

    public function testCreateBuilderForPropertyCreatesFormWithHighestConfidence(): void
    {
        $this->guesser1->expects($this->once())
            ->method('guessType')
            ->with('Application\Author', 'firstName')
            ->willReturn(new TypeGuess(
                \Symfony\Component\Form\Extension\Core\Type\TextType::class,
                ['attr' => ['maxlength' => 10]],
                Guess::MEDIUM_CONFIDENCE
            ));

        $this->guesser2->expects($this->once())
            ->method('guessType')
            ->with('Application\Author', 'firstName')
            ->willReturn(new TypeGuess(
                \Symfony\Component\Form\Extension\Core\Type\PasswordType::class,
                ['attr' => ['maxlength' => 7]],
                Guess::HIGH_CONFIDENCE
            ));

        $factory = $this->getMockFactory(['createNamedBuilder']);

        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('firstName', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, null, ['attr' => ['maxlength' => 7]])
            ->willReturn($this->builder);

        $this->builder = $factory->createBuilderForProperty('Application\Author', 'firstName');

        $this->assertSame($this->builder, $this->builder);
    }

    public function testCreateBuilderCreatesTextFormIfNoGuess(): void
    {
        $this->guesser1->expects($this->once())
            ->method('guessType')
            ->with('Application\Author', 'firstName')
            ->willReturn(null);

        $factory = $this->getMockFactory(['createNamedBuilder']);

        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->willReturn($this->builder);

        $this->builder = $factory->createBuilderForProperty('Application\Author', 'firstName');

        $this->assertSame($this->builder, $this->builder);
    }

    public function testOptionsCanBeOverridden(): void
    {
        $this->guesser1->expects($this->once())
            ->method('guessType')
            ->with('Application\Author', 'firstName')
            ->willReturn(new TypeGuess(
                \Symfony\Component\Form\Extension\Core\Type\TextType::class,
                ['attr' => ['class' => 'foo', 'maxlength' => 10]],
                Guess::MEDIUM_CONFIDENCE
            ));

        $factory = $this->getMockFactory(['createNamedBuilder']);

        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class, null, ['attr' => ['class' => 'foo', 'maxlength' => 11]])
            ->willReturn($this->builder);

        $this->builder = $factory->createBuilderForProperty(
            'Application\Author',
            'firstName',
            null,
            ['attr' => ['maxlength' => 11]]
        );

        $this->assertSame($this->builder, $this->builder);
    }

    public function testCreateBuilderUsesMaxLengthIfFound(): void
    {
        $this->guesser1->expects($this->once())
            ->method('guessMaxLength')
            ->with('Application\Author', 'firstName')
            ->willReturn(new ValueGuess(
                    15,
                    Guess::MEDIUM_CONFIDENCE
                ));

        $this->guesser2->expects($this->once())
            ->method('guessMaxLength')
            ->with('Application\Author', 'firstName')
            ->willReturn(new ValueGuess(
                    20,
                    Guess::HIGH_CONFIDENCE
                ));

        $factory = $this->getMockFactory(['createNamedBuilder']);

        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class, null, ['attr' => ['maxlength' => 20]])
            ->willReturn($this->builder);

        $this->builder = $factory->createBuilderForProperty(
            'Application\Author',
            'firstName'
        );

        $this->assertSame($this->builder, $this->builder);
    }

    public function testCreateBuilderUsesMaxLengthAndPattern(): void
    {
        $this->guesser1->expects($this->once())
            ->method('guessMaxLength')
            ->with('Application\Author', 'firstName')
            ->willReturn(new ValueGuess(
                20,
                Guess::HIGH_CONFIDENCE
            ));

        $this->guesser2->expects($this->once())
            ->method('guessPattern')
            ->with('Application\Author', 'firstName')
            ->willReturn(new ValueGuess(
                '.{5,}',
                Guess::HIGH_CONFIDENCE
            ));

        $factory = $this->getMockFactory(['createNamedBuilder']);

        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class, null, ['attr' => ['maxlength' => 20, 'pattern' => '.{5,}', 'class' => 'tinymce']])
            ->willReturn($this->builder);

        $this->builder = $factory->createBuilderForProperty(
            'Application\Author',
            'firstName',
            null,
            ['attr' => ['class' => 'tinymce']]
        );

        $this->assertSame($this->builder, $this->builder);
    }

    public function testCreateBuilderUsesRequiredSettingWithHighestConfidence(): void
    {
        $this->guesser1->expects($this->once())
            ->method('guessRequired')
            ->with('Application\Author', 'firstName')
            ->willReturn(new ValueGuess(
                    true,
                    Guess::MEDIUM_CONFIDENCE
                ));

        $this->guesser2->expects($this->once())
            ->method('guessRequired')
            ->with('Application\Author', 'firstName')
            ->willReturn(new ValueGuess(
                    false,
                    Guess::HIGH_CONFIDENCE
                ));

        $factory = $this->getMockFactory(['createNamedBuilder']);

        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class, null, ['required' => false])
            ->willReturn($this->builder);

        $this->builder = $factory->createBuilderForProperty(
            'Application\Author',
            'firstName'
        );

        $this->assertSame($this->builder, $this->builder);
    }

    public function testCreateBuilderUsesPatternIfFound(): void
    {
        $this->guesser1->expects($this->once())
            ->method('guessPattern')
            ->with('Application\Author', 'firstName')
            ->willReturn(new ValueGuess(
                    '[a-z]',
                    Guess::MEDIUM_CONFIDENCE
                ));

        $this->guesser2->expects($this->once())
            ->method('guessPattern')
            ->with('Application\Author', 'firstName')
            ->willReturn(new ValueGuess(
                    '[a-zA-Z]',
                    Guess::HIGH_CONFIDENCE
                ));

        $factory = $this->getMockFactory(['createNamedBuilder']);

        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->with('firstName', \Symfony\Component\Form\Extension\Core\Type\TextType::class, null, ['attr' => ['pattern' => '[a-zA-Z]']])
            ->willReturn($this->builder);

        $this->builder = $factory->createBuilderForProperty(
            'Application\Author',
            'firstName'
        );

        $this->assertSame($this->builder, $this->builder);
    }

    protected function createForm()
    {
        $formBuilder = new FormBuilder('', null, new EventDispatcher(), $this->factory);

        return $formBuilder->getForm();
    }

    private function getMockFactory(array $methods = [])
    {
        return $this->getMockBuilder(\Symfony\Component\Form\FormFactory::class)
            ->setMethods($methods)
            ->setConstructorArgs([$this->registry])
            ->getMock();
    }

    private function getMockResolvedType()
    {
        return $this->getMockBuilder(\Symfony\Component\Form\ResolvedFormTypeInterface::class)->getMock();
    }
}
