<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Tests\Fixtures\ClassConstraint;
use Symfony\Component\Validator\Tests\Fixtures\ConstraintA;
use Symfony\Component\Validator\Tests\Fixtures\ConstraintB;
use Symfony\Component\Validator\Tests\Fixtures\PropertyConstraint;

class ClassMetadataTest extends TestCase
{
    final const CLASSNAME = 'Symfony\Component\Validator\Tests\Fixtures\Entity';

    final const PARENTCLASS = 'Symfony\Component\Validator\Tests\Fixtures\EntityParent';

    final const PROVIDERCLASS = 'Symfony\Component\Validator\Tests\Fixtures\GroupSequenceProviderEntity';

    final const PROVIDERCHILDCLASS = 'Symfony\Component\Validator\Tests\Fixtures\GroupSequenceProviderChildEntity';

    protected $metadata;

    protected function setUp()
    {
        $this->metadata = new ClassMetadata(self::CLASSNAME);
    }

    protected function tearDown()
    {
        $this->metadata = null;
    }

    public function testAddConstraintDoesNotAcceptValid(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\ConstraintDefinitionException::class);

        $this->metadata->addConstraint(new Valid());
    }

    public function testAddConstraintRequiresClassConstraints(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\ConstraintDefinitionException::class);

        $this->metadata->addConstraint(new PropertyConstraint());
    }

    public function testAddCompositeConstraintRejectsNestedPropertyConstraints(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The constraint "Symfony\Component\Validator\Tests\Fixtures\PropertyConstraint" cannot be put on classes.');

        $this->metadata->addConstraint(new ClassCompositeConstraint([new PropertyConstraint()]));
    }

    public function testAddCompositeConstraintAcceptsNestedClassConstraints(): void
    {
        $this->metadata->addConstraint($constraint = new ClassCompositeConstraint([new ClassConstraint()]));
        $this->assertSame($this->metadata->getConstraints(), [$constraint]);
    }

    public function testAddPropertyConstraints(): void
    {
        $this->metadata->addPropertyConstraint('firstName', new ConstraintA());
        $this->metadata->addPropertyConstraint('lastName', new ConstraintB());

        $this->assertEquals(['firstName', 'lastName'], $this->metadata->getConstrainedProperties());
    }

    public function testAddMultiplePropertyConstraints(): void
    {
        $this->metadata->addPropertyConstraints('lastName', [new ConstraintA(), new ConstraintB()]);

        $constraints = [
            new ConstraintA(['groups' => ['Default', 'Entity']]),
            new ConstraintB(['groups' => ['Default', 'Entity']]),
        ];

        $properties = $this->metadata->getPropertyMetadata('lastName');

        $this->assertCount(1, $properties);
        $this->assertEquals('lastName', $properties[0]->getName());
        $this->assertEquals($constraints, $properties[0]->getConstraints());
    }

    public function testAddGetterConstraints(): void
    {
        $this->metadata->addGetterConstraint('lastName', new ConstraintA());
        $this->metadata->addGetterConstraint('lastName', new ConstraintB());

        $constraints = [
            new ConstraintA(['groups' => ['Default', 'Entity']]),
            new ConstraintB(['groups' => ['Default', 'Entity']]),
        ];

        $properties = $this->metadata->getPropertyMetadata('lastName');

        $this->assertCount(1, $properties);
        $this->assertEquals('getLastName', $properties[0]->getName());
        $this->assertEquals($constraints, $properties[0]->getConstraints());
    }

    public function testAddMultipleGetterConstraints(): void
    {
        $this->metadata->addGetterConstraints('lastName', [new ConstraintA(), new ConstraintB()]);

        $constraints = [
            new ConstraintA(['groups' => ['Default', 'Entity']]),
            new ConstraintB(['groups' => ['Default', 'Entity']]),
        ];

        $properties = $this->metadata->getPropertyMetadata('lastName');

        $this->assertCount(1, $properties);
        $this->assertEquals('getLastName', $properties[0]->getName());
        $this->assertEquals($constraints, $properties[0]->getConstraints());
    }

    public function testMergeConstraintsMergesClassConstraints(): void
    {
        $parent = new ClassMetadata(self::PARENTCLASS);
        $parent->addConstraint(new ConstraintA());

        $this->metadata->mergeConstraints($parent);
        $this->metadata->addConstraint(new ConstraintA());

        $constraints = [
            new ConstraintA(['groups' => [
                'Default',
                'EntityParent',
                'Entity',
            ]]),
            new ConstraintA(['groups' => [
                'Default',
                'Entity',
            ]]),
        ];

        $this->assertEquals($constraints, $this->metadata->getConstraints());
    }

    public function testMergeConstraintsMergesMemberConstraints(): void
    {
        $parent = new ClassMetadata(self::PARENTCLASS);
        $parent->addPropertyConstraint('firstName', new ConstraintA());
        $parent->addPropertyConstraint('firstName', new ConstraintB(['groups' => 'foo']));

        $this->metadata->mergeConstraints($parent);
        $this->metadata->addPropertyConstraint('firstName', new ConstraintA());

        $constraintA1 = new ConstraintA(['groups' => [
            'Default',
            'EntityParent',
            'Entity',
        ]]);
        $constraintA2 = new ConstraintA(['groups' => [
            'Default',
            'Entity',
        ]]);
        $constraintB = new ConstraintB([
            'groups' => ['foo'],
        ]);

        $constraints = [
            $constraintA1,
            $constraintB,
            $constraintA2,
        ];

        $constraintsByGroup = [
            'Default' => [
                $constraintA1,
                $constraintA2,
            ],
            'EntityParent' => [
                $constraintA1,
            ],
            'Entity' => [
                $constraintA1,
                $constraintA2,
            ],
            'foo' => [
                $constraintB,
            ],
        ];

        $members = $this->metadata->getPropertyMetadata('firstName');

        $this->assertCount(1, $members);
        $this->assertEquals(self::PARENTCLASS, $members[0]->getClassName());
        $this->assertEquals($constraints, $members[0]->getConstraints());
        $this->assertEquals($constraintsByGroup, $members[0]->constraintsByGroup);
    }

    public function testMemberMetadatas(): void
    {
        $this->metadata->addPropertyConstraint('firstName', new ConstraintA());

        $this->assertTrue($this->metadata->hasPropertyMetadata('firstName'));
        $this->assertFalse($this->metadata->hasPropertyMetadata('non_existent_field'));
    }

    public function testMergeConstraintsKeepsPrivateMembersSeparate(): void
    {
        $parent = new ClassMetadata(self::PARENTCLASS);
        $parent->addPropertyConstraint('internal', new ConstraintA());

        $this->metadata->mergeConstraints($parent);
        $this->metadata->addPropertyConstraint('internal', new ConstraintA());

        $parentConstraints = [
            new ConstraintA(['groups' => [
                'Default',
                'EntityParent',
                'Entity',
            ]]),
        ];
        $constraints = [
            new ConstraintA(['groups' => [
                'Default',
                'Entity',
            ]]),
        ];

        $members = $this->metadata->getPropertyMetadata('internal');

        $this->assertCount(2, $members);
        $this->assertEquals(self::PARENTCLASS, $members[0]->getClassName());
        $this->assertEquals($parentConstraints, $members[0]->getConstraints());
        $this->assertEquals(self::CLASSNAME, $members[1]->getClassName());
        $this->assertEquals($constraints, $members[1]->getConstraints());
    }

    public function testGetReflectionClass(): void
    {
        $reflClass = new \ReflectionClass(self::CLASSNAME);

        $this->assertEquals($reflClass, $this->metadata->getReflectionClass());
    }

    public function testSerialize(): void
    {
        $this->metadata->addConstraint(new ConstraintA(['property1' => 'A']));
        $this->metadata->addConstraint(new ConstraintB(['groups' => 'TestGroup']));
        $this->metadata->addPropertyConstraint('firstName', new ConstraintA());
        $this->metadata->addGetterConstraint('lastName', new ConstraintB());

        $metadata = unserialize(serialize($this->metadata));

        $this->assertEquals($this->metadata, $metadata);
    }

    public function testGroupSequencesWorkIfContainingDefaultGroup(): void
    {
        $this->metadata->setGroupSequence(['Foo', $this->metadata->getDefaultGroup()]);

        $this->assertInstanceOf(\Symfony\Component\Validator\Constraints\GroupSequence::class, $this->metadata->getGroupSequence());
    }

    public function testGroupSequencesFailIfNotContainingDefaultGroup(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\GroupDefinitionException::class);
        $this->metadata->setGroupSequence(['Foo', 'Bar']);
    }

    public function testGroupSequencesFailIfContainingDefault(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\GroupDefinitionException::class);
        $this->metadata->setGroupSequence(['Foo', $this->metadata->getDefaultGroup(), Constraint::DEFAULT_GROUP]);
    }

    public function testGroupSequenceFailsIfGroupSequenceProviderIsSet(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\GroupDefinitionException::class);
        $metadata = new ClassMetadata(self::PROVIDERCLASS);
        $metadata->setGroupSequenceProvider(true);
        $metadata->setGroupSequence(['GroupSequenceProviderEntity', 'Foo']);
    }

    public function testGroupSequenceProviderFailsIfGroupSequenceIsSet(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\GroupDefinitionException::class);
        $metadata = new ClassMetadata(self::PROVIDERCLASS);
        $metadata->setGroupSequence(['GroupSequenceProviderEntity', 'Foo']);
        $metadata->setGroupSequenceProvider(true);
    }

    public function testGroupSequenceProviderFailsIfDomainClassIsInvalid(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\GroupDefinitionException::class);
        $metadata = new ClassMetadata('stdClass');
        $metadata->setGroupSequenceProvider(true);
    }

    public function testGroupSequenceProvider(): void
    {
        $metadata = new ClassMetadata(self::PROVIDERCLASS);
        $metadata->setGroupSequenceProvider(true);
        $this->assertTrue($metadata->isGroupSequenceProvider());
    }

    public function testMergeConstraintsMergesGroupSequenceProvider(): void
    {
        $parent = new ClassMetadata(self::PROVIDERCLASS);
        $parent->setGroupSequenceProvider(true);

        $metadata = new ClassMetadata(self::PROVIDERCHILDCLASS);
        $metadata->mergeConstraints($parent);

        $this->assertTrue($metadata->isGroupSequenceProvider());
    }

    /**
     * https://github.com/symfony/symfony/issues/11604.
     */
    public function testGetPropertyMetadataReturnsEmptyArrayWithoutConfiguredMetadata(): void
    {
        $this->assertCount(0, $this->metadata->getPropertyMetadata('foo'), '->getPropertyMetadata() returns an empty collection if no metadata is configured for the given property');
    }
}

class ClassCompositeConstraint extends Composite
{
    public $nested;

    public function getDefaultOption()
    {
        return $this->getCompositeOption();
    }

    protected function getCompositeOption(): string
    {
        return 'nested';
    }

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}