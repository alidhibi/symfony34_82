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

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Tests\Fixtures\FixedDataTransformer;
use Symfony\Component\Form\Tests\Fixtures\FixedFilterListener;
use Symfony\Component\PropertyAccess\PropertyPath;

class SimpleFormTest_Countable implements \Countable
{
    private $count;

    public function __construct($count)
    {
        $this->count = $count;
    }

    public function count()
    {
        return $this->count;
    }
}

class SimpleFormTest_Traversable implements \IteratorAggregate
{
    private readonly \ArrayIterator $iterator;

    public function __construct($count)
    {
        $this->iterator = new \ArrayIterator($count > 0 ? array_fill(0, $count, 'Foo') : []);
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }
}

class SimpleFormTest extends AbstractFormTest
{
    /**
     * @dataProvider provideFormNames
     */
    public function testGetPropertyPath(string|int|null $name, ?\Symfony\Component\PropertyAccess\PropertyPath $propertyPath): void
    {
        $config = new FormConfigBuilder($name, null, $this->dispatcher);
        $form = new Form($config);

        $this->assertEquals($propertyPath, $form->getPropertyPath());
    }

    public function provideFormNames(): \Generator
    {
        yield [null, null];
        yield ['', null];
        yield ['0', new PropertyPath('0')];
        yield [0, new PropertyPath('0')];
        yield ['name', new PropertyPath('name')];
    }

    public function testDataIsInitializedToConfiguredValue(): void
    {
        $model = new FixedDataTransformer([
            'default' => 'foo',
        ]);
        $view = new FixedDataTransformer([
            'foo' => 'bar',
        ]);

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->addViewTransformer($view);
        $config->addModelTransformer($model);
        $config->setData('default');

        $form = new Form($config);

        $this->assertSame('default', $form->getData());
        $this->assertSame('foo', $form->getNormData());
        $this->assertSame('bar', $form->getViewData());
    }

    public function testDataTransformationFailure(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('Unable to transform data for property path "name": No mapping for value "arg"');
        $model = new FixedDataTransformer([
            'default' => 'foo',
        ]);
        $view = new FixedDataTransformer([
            'foo' => 'bar',
        ]);

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->addViewTransformer($view);
        $config->addModelTransformer($model);
        $config->setData('arg');

        $form = new Form($config);

        $form->getData();
    }

    // https://github.com/symfony/symfony/commit/d4f4038f6daf7cf88ca7c7ab089473cce5ebf7d8#commitcomment-1632879
    public function testDataIsInitializedFromSubmit(): void
    {
        $preSetData = false;
        $preSubmit = false;

        $mock = $this->getMockBuilder('\stdClass')
            ->setMethods(['preSetData', 'preSubmit'])
            ->getMock();
        $mock->expects($this->once())
            ->method('preSetData')
            ->with($this->callback(static function () use (&$preSetData, $preSubmit) : bool {
                $preSetData = true;
                return false === $preSubmit;
            }));
        $mock->expects($this->once())
            ->method('preSubmit')
            ->with($this->callback(static function () use ($preSetData, &$preSubmit) : bool {
                $preSubmit = true;
                return false === $preSetData;
            }));

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->addEventListener(FormEvents::PRE_SET_DATA, [$mock, 'preSetData']);
        $config->addEventListener(FormEvents::PRE_SUBMIT, [$mock, 'preSubmit']);

        $form = new Form($config);

        // no call to setData() or similar where the object would be
        // initialized otherwise

        $form->submit('foobar');
    }

    // https://github.com/symfony/symfony/pull/7789
    public function testFalseIsConvertedToNull(): void
    {
        $mock = $this->getMockBuilder('\stdClass')
            ->setMethods(['preSubmit'])
            ->getMock();
        $mock->expects($this->once())
            ->method('preSubmit')
            ->with($this->callback(static fn($event): bool => null === $event->getData()));

        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->addEventListener(FormEvents::PRE_SUBMIT, [$mock, 'preSubmit']);

        $form = new Form($config);

        $form->submit(false);

        $this->assertTrue($form->isValid());
        $this->assertNull($form->getData());
    }

    public function testSubmitThrowsExceptionIfAlreadySubmitted(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\AlreadySubmittedException::class);
        $this->form->submit([]);
        $this->form->submit([]);
    }

    public function testSubmitIsIgnoredIfDisabled(): void
    {
        $form = $this->getBuilder()
            ->setDisabled(true)
            ->setData('initial')
            ->getForm();

        $form->submit('new');

        $this->assertEquals('initial', $form->getData());
        $this->assertTrue($form->isSubmitted());
    }

    public function testNeverRequiredIfParentNotRequired(): void
    {
        $parent = $this->getBuilder()->setRequired(false)->getForm();
        $child = $this->getBuilder()->setRequired(true)->getForm();

        $child->setParent($parent);

        $this->assertFalse($child->isRequired());
    }

    public function testRequired(): void
    {
        $parent = $this->getBuilder()->setRequired(true)->getForm();
        $child = $this->getBuilder()->setRequired(true)->getForm();

        $child->setParent($parent);

        $this->assertTrue($child->isRequired());
    }

    public function testNotRequired(): void
    {
        $parent = $this->getBuilder()->setRequired(true)->getForm();
        $child = $this->getBuilder()->setRequired(false)->getForm();

        $child->setParent($parent);

        $this->assertFalse($child->isRequired());
    }

    /**
     * @dataProvider getDisabledStates
     */
    public function testAlwaysDisabledIfParentDisabled(bool $parentDisabled, bool $disabled, bool $result): void
    {
        $parent = $this->getBuilder()->setDisabled($parentDisabled)->getForm();
        $child = $this->getBuilder()->setDisabled($disabled)->getForm();

        $child->setParent($parent);

        $this->assertSame($result, $child->isDisabled());
    }

    public function getDisabledStates(): array
    {
        return [
            // parent, button, result
            [true, true, true],
            [true, false, true],
            [false, true, true],
            [false, false, false],
        ];
    }

    public function testGetRootReturnsRootOfParent(): void
    {
        $root = $this->createForm();

        $parent = $this->createForm();
        $parent->setParent($root);

        $this->form->setParent($parent);

        $this->assertSame($root, $this->form->getRoot());
    }

    public function testGetRootReturnsSelfIfNoParent(): void
    {
        $this->assertSame($this->form, $this->form->getRoot());
    }

    public function testEmptyIfEmptyArray(): void
    {
        $this->form->setData([]);

        $this->assertTrue($this->form->isEmpty());
    }

    public function testEmptyIfEmptyCountable(): void
    {
        $this->form = new Form(new FormConfigBuilder('name', __NAMESPACE__.'\SimpleFormTest_Countable', $this->dispatcher));

        $this->form->setData(new SimpleFormTest_Countable(0));

        $this->assertTrue($this->form->isEmpty());
    }

    public function testNotEmptyIfFilledCountable(): void
    {
        $this->form = new Form(new FormConfigBuilder('name', __NAMESPACE__.'\SimpleFormTest_Countable', $this->dispatcher));

        $this->form->setData(new SimpleFormTest_Countable(1));

        $this->assertFalse($this->form->isEmpty());
    }

    public function testEmptyIfEmptyTraversable(): void
    {
        $this->form = new Form(new FormConfigBuilder('name', __NAMESPACE__.'\SimpleFormTest_Traversable', $this->dispatcher));

        $this->form->setData(new SimpleFormTest_Traversable(0));

        $this->assertTrue($this->form->isEmpty());
    }

    public function testNotEmptyIfFilledTraversable(): void
    {
        $this->form = new Form(new FormConfigBuilder('name', __NAMESPACE__.'\SimpleFormTest_Traversable', $this->dispatcher));

        $this->form->setData(new SimpleFormTest_Traversable(1));

        $this->assertFalse($this->form->isEmpty());
    }

    public function testEmptyIfNull(): void
    {
        $this->form->setData(null);

        $this->assertTrue($this->form->isEmpty());
    }

    public function testEmptyIfEmptyString(): void
    {
        $this->form->setData('');

        $this->assertTrue($this->form->isEmpty());
    }

    public function testNotEmptyIfText(): void
    {
        $this->form->setData('foobar');

        $this->assertFalse($this->form->isEmpty());
    }

    public function testValidIfSubmitted(): void
    {
        $form = $this->getBuilder()->getForm();
        $form->submit('foobar');

        $this->assertTrue($form->isValid());
    }

    public function testValidIfSubmittedAndDisabled(): void
    {
        $form = $this->getBuilder()->setDisabled(true)->getForm();
        $form->submit('foobar');

        $this->assertTrue($form->isValid());
    }

    /**
     * @group legacy
     * @expectedDeprecation Call Form::isValid() with an unsubmitted form %s.
     */
    public function testNotValidIfNotSubmitted(): void
    {
        $this->assertFalse($this->form->isValid());
    }

    public function testNotValidIfErrors(): void
    {
        $form = $this->getBuilder()->getForm();
        $form->submit('foobar');
        $form->addError(new FormError('Error!'));

        $this->assertFalse($form->isValid());
    }

    public function testHasErrors(): void
    {
        $this->form->addError(new FormError('Error!'));

        $this->assertCount(1, $this->form->getErrors());
    }

    public function testHasNoErrors(): void
    {
        $this->assertCount(0, $this->form->getErrors());
    }

    public function testSetParentThrowsExceptionIfAlreadySubmitted(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\AlreadySubmittedException::class);
        $this->form->submit([]);
        $this->form->setParent($this->getBuilder('parent')->getForm());
    }

    public function testSubmitted(): void
    {
        $form = $this->getBuilder()->getForm();
        $form->submit('foobar');

        $this->assertTrue($form->isSubmitted());
    }

    public function testNotSubmitted(): void
    {
        $this->assertFalse($this->form->isSubmitted());
    }

    public function testSetDataThrowsExceptionIfAlreadySubmitted(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\AlreadySubmittedException::class);
        $this->form->submit([]);
        $this->form->setData(null);
    }

    public function testSetDataClonesObjectIfNotByReference(): void
    {
        $data = new \stdClass();
        $form = $this->getBuilder('name', null, '\stdClass')->setByReference(false)->getForm();
        $form->setData($data);

        $this->assertNotSame($data, $form->getData());
        $this->assertEquals($data, $form->getData());
    }

    public function testSetDataDoesNotCloneObjectIfByReference(): void
    {
        $data = new \stdClass();
        $form = $this->getBuilder('name', null, '\stdClass')->setByReference(true)->getForm();
        $form->setData($data);

        $this->assertSame($data, $form->getData());
    }

    public function testSetDataExecutesTransformationChain(): void
    {
        // use real event dispatcher now
        $form = $this->getBuilder('name', new EventDispatcher())
            ->addEventSubscriber(new FixedFilterListener([
                'preSetData' => [
                    'app' => 'filtered',
                ],
            ]))
            ->addModelTransformer(new FixedDataTransformer([
                '' => '',
                'filtered' => 'norm',
            ]))
            ->addViewTransformer(new FixedDataTransformer([
                '' => '',
                'norm' => 'client',
            ]))
            ->getForm();

        $form->setData('app');

        $this->assertEquals('filtered', $form->getData());
        $this->assertEquals('norm', $form->getNormData());
        $this->assertEquals('client', $form->getViewData());
    }

    public function testSetDataExecutesViewTransformersInOrder(): void
    {
        $form = $this->getBuilder()
            ->addViewTransformer(new FixedDataTransformer([
                '' => '',
                'first' => 'second',
            ]))
            ->addViewTransformer(new FixedDataTransformer([
                '' => '',
                'second' => 'third',
            ]))
            ->getForm();

        $form->setData('first');

        $this->assertEquals('third', $form->getViewData());
    }

    public function testSetDataExecutesModelTransformersInReverseOrder(): void
    {
        $form = $this->getBuilder()
            ->addModelTransformer(new FixedDataTransformer([
                '' => '',
                'second' => 'third',
            ]))
            ->addModelTransformer(new FixedDataTransformer([
                '' => '',
                'first' => 'second',
            ]))
            ->getForm();

        $form->setData('first');

        $this->assertEquals('third', $form->getNormData());
    }

    /*
     * When there is no data transformer, the data must have the same format
     * in all three representations
     */
    public function testSetDataConvertsScalarToStringIfNoTransformer(): void
    {
        $form = $this->getBuilder()->getForm();

        $form->setData(1);

        $this->assertSame('1', $form->getData());
        $this->assertSame('1', $form->getNormData());
        $this->assertSame('1', $form->getViewData());
    }

    /*
     * Data in client format should, if possible, always be a string to
     * facilitate differentiation between '0' and ''
     */
    public function testSetDataConvertsScalarToStringIfOnlyModelTransformer(): void
    {
        $form = $this->getBuilder()
            ->addModelTransformer(new FixedDataTransformer([
            '' => '',
            1 => 23,
        ]))
            ->getForm();

        $form->setData(1);

        $this->assertSame(1, $form->getData());
        $this->assertSame(23, $form->getNormData());
        $this->assertSame('23', $form->getViewData());
    }

    /*
     * NULL remains NULL in app and norm format to remove the need to treat
     * empty values and NULL explicitly in the application
     */
    public function testSetDataConvertsNullToStringIfNoTransformer(): void
    {
        $form = $this->getBuilder()->getForm();

        $form->setData(null);

        $this->assertNull($form->getData());
        $this->assertNull($form->getNormData());
        $this->assertSame('', $form->getViewData());
    }

    public function testSetDataIsIgnoredIfDataIsLocked(): void
    {
        $form = $this->getBuilder()
            ->setData('default')
            ->setDataLocked(true)
            ->getForm();

        $form->setData('foobar');

        $this->assertSame('default', $form->getData());
    }

    public function testPreSetDataChangesDataIfDataIsLocked(): void
    {
        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config
            ->setData('default')
            ->setDataLocked(true)
            ->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) : void {
                $event->setData('foobar');
            });
        $form = new Form($config);

        $this->assertSame('foobar', $form->getData());
        $this->assertSame('foobar', $form->getNormData());
        $this->assertSame('foobar', $form->getViewData());
    }

    public function testSubmitConvertsEmptyToNullIfNoTransformer(): void
    {
        $form = $this->getBuilder()->getForm();

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertNull($form->getNormData());
        $this->assertSame('', $form->getViewData());
    }

    public function testSubmitExecutesTransformationChain(): void
    {
        // use real event dispatcher now
        $form = $this->getBuilder('name', new EventDispatcher())
            ->addEventSubscriber(new FixedFilterListener([
                'preSubmit' => [
                    'client' => 'filteredclient',
                ],
                'onSubmit' => [
                    'norm' => 'filterednorm',
                ],
            ]))
            ->addViewTransformer(new FixedDataTransformer([
                '' => '',
                // direction is reversed!
                'norm' => 'filteredclient',
                'filterednorm' => 'cleanedclient',
            ]))
            ->addModelTransformer(new FixedDataTransformer([
                '' => '',
                // direction is reversed!
                'app' => 'filterednorm',
            ]))
            ->getForm();

        $form->submit('client');

        $this->assertEquals('app', $form->getData());
        $this->assertEquals('filterednorm', $form->getNormData());
        $this->assertEquals('cleanedclient', $form->getViewData());
    }

    public function testSubmitExecutesViewTransformersInReverseOrder(): void
    {
        $form = $this->getBuilder()
            ->addViewTransformer(new FixedDataTransformer([
                '' => '',
                'third' => 'second',
            ]))
            ->addViewTransformer(new FixedDataTransformer([
                '' => '',
                'second' => 'first',
            ]))
            ->getForm();

        $form->submit('first');

        $this->assertEquals('third', $form->getNormData());
    }

    public function testSubmitExecutesModelTransformersInOrder(): void
    {
        $form = $this->getBuilder()
            ->addModelTransformer(new FixedDataTransformer([
                '' => '',
                'second' => 'first',
            ]))
            ->addModelTransformer(new FixedDataTransformer([
                '' => '',
                'third' => 'second',
            ]))
            ->getForm();

        $form->submit('first');

        $this->assertEquals('third', $form->getData());
    }

    public function testSynchronizedByDefault(): void
    {
        $this->assertTrue($this->form->isSynchronized());
    }

    public function testSynchronizedAfterSubmission(): void
    {
        $this->form->submit('foobar');

        $this->assertTrue($this->form->isSynchronized());
    }

    public function testNotSynchronizedIfViewReverseTransformationFailed(): void
    {
        $transformer = $this->getDataTransformer();
        $transformer->expects($this->once())
            ->method('reverseTransform')
            ->willThrowException(new TransformationFailedException());

        $form = $this->getBuilder()
            ->addViewTransformer($transformer)
            ->getForm();

        $form->submit('foobar');

        $this->assertFalse($form->isSynchronized());
    }

    public function testNotSynchronizedIfModelReverseTransformationFailed(): void
    {
        $transformer = $this->getDataTransformer();
        $transformer->expects($this->once())
            ->method('reverseTransform')
            ->willThrowException(new TransformationFailedException());

        $form = $this->getBuilder()
            ->addModelTransformer($transformer)
            ->getForm();

        $form->submit('foobar');

        $this->assertFalse($form->isSynchronized());
    }

    public function testEmptyDataCreatedBeforeTransforming(): void
    {
        $form = $this->getBuilder()
            ->setEmptyData('foo')
            ->addViewTransformer(new FixedDataTransformer([
                '' => '',
                // direction is reversed!
                'bar' => 'foo',
            ]))
            ->getForm();

        $form->submit('');

        $this->assertEquals('bar', $form->getData());
    }

    public function testEmptyDataFromClosure(): void
    {
        $form = $this->getBuilder()
            ->setEmptyData(function ($form): string {
                // the form instance is passed to the closure to allow use
                // of form data when creating the empty value
                $this->assertInstanceOf(\Symfony\Component\Form\FormInterface::class, $form);

                return 'foo';
            })
            ->addViewTransformer(new FixedDataTransformer([
                '' => '',
                // direction is reversed!
                'bar' => 'foo',
            ]))
            ->getForm();

        $form->submit('');

        $this->assertEquals('bar', $form->getData());
    }

    public function testSubmitResetsErrors(): void
    {
        $this->form->addError(new FormError('Error!'));
        $this->form->submit('foobar');

        $this->assertCount(0, $this->form->getErrors());
    }

    public function testCreateView(): void
    {
        $type = $this->getMockBuilder(\Symfony\Component\Form\ResolvedFormTypeInterface::class)->getMock();
        $view = $this->getMockBuilder(\Symfony\Component\Form\FormView::class)->getMock();
        $form = $this->getBuilder()->setType($type)->getForm();

        $type->expects($this->once())
            ->method('createView')
            ->with($form)
            ->willReturn($view);

        $this->assertSame($view, $form->createView());
    }

    public function testCreateViewWithParent(): void
    {
        $type = $this->getMockBuilder(\Symfony\Component\Form\ResolvedFormTypeInterface::class)->getMock();
        $view = $this->getMockBuilder(\Symfony\Component\Form\FormView::class)->getMock();
        $parentType = $this->getMockBuilder(\Symfony\Component\Form\ResolvedFormTypeInterface::class)->getMock();
        $parentForm = $this->getBuilder()->setType($parentType)->getForm();
        $parentView = $this->getMockBuilder(\Symfony\Component\Form\FormView::class)->getMock();
        $form = $this->getBuilder()->setType($type)->getForm();
        $form->setParent($parentForm);

        $parentType->expects($this->once())
            ->method('createView')
            ->willReturn($parentView);

        $type->expects($this->once())
            ->method('createView')
            ->with($form, $parentView)
            ->willReturn($view);

        $this->assertSame($view, $form->createView());
    }

    public function testCreateViewWithExplicitParent(): void
    {
        $type = $this->getMockBuilder(\Symfony\Component\Form\ResolvedFormTypeInterface::class)->getMock();
        $view = $this->getMockBuilder(\Symfony\Component\Form\FormView::class)->getMock();
        $parentView = $this->getMockBuilder(\Symfony\Component\Form\FormView::class)->getMock();
        $form = $this->getBuilder()->setType($type)->getForm();

        $type->expects($this->once())
            ->method('createView')
            ->with($form, $parentView)
            ->willReturn($view);

        $this->assertSame($view, $form->createView($parentView));
    }

    public function testFormCanHaveEmptyName(): void
    {
        $form = $this->getBuilder('')->getForm();

        $this->assertEquals('', $form->getName());
    }

    public function testSetNullParentWorksWithEmptyName(): void
    {
        $form = $this->getBuilder('')->getForm();
        $form->setParent(null);

        $this->assertNull($form->getParent());
    }

    public function testFormCannotHaveEmptyNameNotInRootLevel(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\LogicException::class);
        $this->expectExceptionMessage('A form with an empty name cannot have a parent form.');
        $this->getBuilder()
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->add($this->getBuilder(''))
            ->getForm();
    }

    public function testGetPropertyPathReturnsConfiguredPath(): void
    {
        $form = $this->getBuilder()->setPropertyPath('address.street')->getForm();

        $this->assertEquals(new PropertyPath('address.street'), $form->getPropertyPath());
    }

    // see https://github.com/symfony/symfony/issues/3903
    public function testGetPropertyPathDefaultsToNameIfParentHasDataClass(): void
    {
        $parent = $this->getBuilder(null, null, 'stdClass')
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->getForm();
        $form = $this->getBuilder('name')->getForm();
        $parent->add($form);

        $this->assertEquals(new PropertyPath('name'), $form->getPropertyPath());
    }

    // see https://github.com/symfony/symfony/issues/3903
    public function testGetPropertyPathDefaultsToIndexedNameIfParentDataClassIsNull(): void
    {
        $parent = $this->getBuilder()
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->getForm();
        $form = $this->getBuilder('name')->getForm();
        $parent->add($form);

        $this->assertEquals(new PropertyPath('[name]'), $form->getPropertyPath());
    }

    public function testGetPropertyPathDefaultsToNameIfFirstParentWithoutInheritDataHasDataClass(): void
    {
        $grandParent = $this->getBuilder(null, null, 'stdClass')
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->getForm();
        $parent = $this->getBuilder()
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->setInheritData(true)
            ->getForm();
        $form = $this->getBuilder('name')->getForm();
        $grandParent->add($parent);
        $parent->add($form);

        $this->assertEquals(new PropertyPath('name'), $form->getPropertyPath());
    }

    public function testGetPropertyPathDefaultsToIndexedNameIfDataClassOfFirstParentWithoutInheritDataIsNull(): void
    {
        $grandParent = $this->getBuilder()
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->getForm();
        $parent = $this->getBuilder()
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->setInheritData(true)
            ->getForm();
        $form = $this->getBuilder('name')->getForm();
        $grandParent->add($parent);
        $parent->add($form);

        $this->assertEquals(new PropertyPath('[name]'), $form->getPropertyPath());
    }

    public function testViewDataMayBeObjectIfDataClassIsNull(): void
    {
        $object = new \stdClass();
        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->addViewTransformer(new FixedDataTransformer([
            '' => '',
            'foo' => $object,
        ]));
        $form = new Form($config);

        $form->setData('foo');

        $this->assertSame($object, $form->getViewData());
    }

    public function testViewDataMayBeArrayAccessIfDataClassIsNull(): void
    {
        $arrayAccess = $this->getMockBuilder('\ArrayAccess')->getMock();
        $config = new FormConfigBuilder('name', null, $this->dispatcher);
        $config->addViewTransformer(new FixedDataTransformer([
            '' => '',
            'foo' => $arrayAccess,
        ]));
        $form = new Form($config);

        $form->setData('foo');

        $this->assertSame($arrayAccess, $form->getViewData());
    }

    public function testViewDataMustBeObjectIfDataClassIsSet(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\LogicException::class);
        $config = new FormConfigBuilder('name', 'stdClass', $this->dispatcher);
        $config->addViewTransformer(new FixedDataTransformer([
            '' => '',
            'foo' => ['bar' => 'baz'],
        ]));
        $form = new Form($config);

        $form->setData('foo');
    }

    public function testSetDataCannotInvokeItself(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $this->expectExceptionMessage('A cycle was detected. Listeners to the PRE_SET_DATA event must not call setData(). You should call setData() on the FormEvent object instead.');
        // Cycle detection to prevent endless loops
        $config = new FormConfigBuilder('name', 'stdClass', $this->dispatcher);
        $config->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) : void {
            $event->getForm()->setData('bar');
        });
        $form = new Form($config);

        $form->setData('foo');
    }

    public function testSubmittingWrongDataIsIgnored(): void
    {
        $called = 0;

        $child = $this->getBuilder('child', $this->dispatcher);
        $child->addEventListener(FormEvents::PRE_SUBMIT, static function () use (&$called) : void {
            ++$called;
        });

        $parent = $this->getBuilder('parent', new EventDispatcher())
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->add($child)
            ->getForm();

        $parent->submit('not-an-array');

        $this->assertSame(0, $called, 'PRE_SUBMIT event listeners are not called for wrong data');
    }

    public function testHandleRequestForwardsToRequestHandler(): void
    {
        $handler = $this->getMockBuilder(\Symfony\Component\Form\RequestHandlerInterface::class)->getMock();

        $form = $this->getBuilder()
            ->setRequestHandler($handler)
            ->getForm();

        $handler->expects($this->once())
            ->method('handleRequest')
            ->with($this->identicalTo($form), 'REQUEST');

        $this->assertSame($form, $form->handleRequest('REQUEST'));
    }

    public function testFormInheritsParentData(): void
    {
        $child = $this->getBuilder('child')
            ->setInheritData(true);

        $parent = $this->getBuilder('parent')
            ->setCompound(true)
            ->setDataMapper($this->getDataMapper())
            ->setData('foo')
            ->addModelTransformer(new FixedDataTransformer([
                'foo' => 'norm[foo]',
            ]))
            ->addViewTransformer(new FixedDataTransformer([
                'norm[foo]' => 'view[foo]',
            ]))
            ->add($child)
            ->getForm();

        $this->assertSame('foo', $parent->get('child')->getData());
        $this->assertSame('norm[foo]', $parent->get('child')->getNormData());
        $this->assertSame('view[foo]', $parent->get('child')->getViewData());
    }

    public function testInheritDataDisallowsSetData(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $form = $this->getBuilder()
            ->setInheritData(true)
            ->getForm();

        $form->setData('foo');
    }

    public function testGetDataRequiresParentToBeSetIfInheritData(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $form = $this->getBuilder()
            ->setInheritData(true)
            ->getForm();

        $form->getData();
    }

    public function testGetNormDataRequiresParentToBeSetIfInheritData(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $form = $this->getBuilder()
            ->setInheritData(true)
            ->getForm();

        $form->getNormData();
    }

    public function testGetViewDataRequiresParentToBeSetIfInheritData(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $form = $this->getBuilder()
            ->setInheritData(true)
            ->getForm();

        $form->getViewData();
    }

    public function testPostSubmitDataIsNullIfInheritData(): void
    {
        $form = $this->getBuilder()
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
                $this->assertNull($event->getData());
            })
            ->setInheritData(true)
            ->getForm();

        $form->submit('foo');
    }

    public function testSubmitIsNeverFiredIfInheritData(): void
    {
        $called = 0;
        $form = $this->getBuilder()
            ->addEventListener(FormEvents::SUBMIT, static function () use (&$called) : void {
                ++$called;
            })
            ->setInheritData(true)
            ->getForm();

        $form->submit('foo');

        $this->assertSame(0, $called, 'The SUBMIT event is not fired when data are inherited from the parent form');
    }

    public function testInitializeSetsDefaultData(): void
    {
        $config = $this->getBuilder()->setData('DEFAULT')->getFormConfig();
        $form = $this->getMockBuilder(\Symfony\Component\Form\Form::class)->setMethods(['setData'])->setConstructorArgs([$config])->getMock();

        $form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo('DEFAULT'));

        /* @var Form $form */
        $form->initialize();
    }

    public function testInitializeFailsIfParent(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $parent = $this->getBuilder()->setRequired(false)->getForm();
        $child = $this->getBuilder()->setRequired(true)->getForm();

        $child->setParent($parent);

        $child->initialize();
    }

    public function testCannotCallGetDataInPreSetDataListenerIfDataHasNotAlreadyBeenSet(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $this->expectExceptionMessage('A cycle was detected. Listeners to the PRE_SET_DATA event must not call getData() if the form data has not already been set. You should call getData() on the FormEvent object instead.');
        $config = new FormConfigBuilder('name', 'stdClass', $this->dispatcher);
        $config->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) : void {
            $event->getForm()->getData();
        });
        $form = new Form($config);

        $form->setData('foo');
    }

    public function testCannotCallGetNormDataInPreSetDataListener(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $this->expectExceptionMessage('A cycle was detected. Listeners to the PRE_SET_DATA event must not call getNormData() if the form data has not already been set.');
        $config = new FormConfigBuilder('name', 'stdClass', $this->dispatcher);
        $config->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) : void {
            $event->getForm()->getNormData();
        });
        $form = new Form($config);

        $form->setData('foo');
    }

    public function testCannotCallGetViewDataInPreSetDataListener(): void
    {
        $this->expectException(\Symfony\Component\Form\Exception\RuntimeException::class);
        $this->expectExceptionMessage('A cycle was detected. Listeners to the PRE_SET_DATA event must not call getViewData() if the form data has not already been set.');
        $config = new FormConfigBuilder('name', 'stdClass', $this->dispatcher);
        $config->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) : void {
            $event->getForm()->getViewData();
        });
        $form = new Form($config);

        $form->setData('foo');
    }

    protected function createForm()
    {
        return $this->getBuilder()->getForm();
    }
}
