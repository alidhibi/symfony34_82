<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\PropertyInfo;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory as LegacyClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException as LegacyMappingException;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException as OrmMappingException;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Extracts data using Doctrine ORM and ODM metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineExtractor implements PropertyListExtractorInterface, PropertyTypeExtractorInterface
{
    private $classMetadataFactory;

    private static ?bool $useDeprecatedConstants = null;

    /**
     * @param ClassMetadataFactory|LegacyClassMetadataFactory $classMetadataFactory
     */
    public function __construct($classMetadataFactory)
    {
        $this->classMetadataFactory = $classMetadataFactory;

        if (null === self::$useDeprecatedConstants) {
            self::$useDeprecatedConstants = !class_exists(Types::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = [])
    {
        try {
            $metadata = $this->classMetadataFactory->getMetadataFor($class);
        } catch (MappingException $exception) {
            return null;
        } catch (OrmMappingException $exception) {
            return null;
        } catch (LegacyMappingException $exception) {
            return null;
        }

        $properties = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());

        if ($metadata instanceof ClassMetadataInfo && class_exists('Doctrine\ORM\Mapping\Embedded') && $metadata->embeddedClasses) {
            $properties = array_filter($properties, fn($property) => false === strpos($property, '.'));

            $properties = [...$properties, ...array_keys($metadata->embeddedClasses)];
        }

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = [])
    {
        try {
            $metadata = $this->classMetadataFactory->getMetadataFor($class);
        } catch (MappingException $exception) {
            return null;
        } catch (OrmMappingException $exception) {
            return null;
        } catch (LegacyMappingException $exception) {
            return null;
        }

        if ($metadata->hasAssociation($property)) {
            $class = $metadata->getAssociationTargetClass($property);

            if ($metadata->isSingleValuedAssociation($property)) {
                if ($metadata instanceof ClassMetadataInfo) {
                    $associationMapping = $metadata->getAssociationMapping($property);

                    $nullable = $this->isAssociationNullable($associationMapping);
                } else {
                    $nullable = false;
                }

                return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, $class)];
            }

            $collectionKeyType = Type::BUILTIN_TYPE_INT;

            if ($metadata instanceof ClassMetadataInfo) {
                $associationMapping = $metadata->getAssociationMapping($property);

                if (isset($associationMapping['indexBy'])) {
                    /** @var ClassMetadataInfo $subMetadata */
                    $subMetadata = $this->classMetadataFactory->getMetadataFor($associationMapping['targetEntity']);

                    // Check if indexBy value is a property
                    $fieldName = $associationMapping['indexBy'];
                    if (null === ($typeOfField = $subMetadata->getTypeOfField($fieldName))) {
                        $fieldName = $subMetadata->getFieldForColumn($associationMapping['indexBy']);
                        //Not a property, maybe a column name?
                        if (null === ($typeOfField = $subMetadata->getTypeOfField($fieldName))) {
                            //Maybe the column name is the association join column?
                            $associationMapping = $subMetadata->getAssociationMapping($fieldName);

                            /** @var ClassMetadataInfo $subMetadata */
                            $indexProperty = $subMetadata->getSingleAssociationReferencedJoinColumnName($fieldName);
                            $subMetadata = $this->classMetadataFactory->getMetadataFor($associationMapping['targetEntity']);
                            $typeOfField = $subMetadata->getTypeOfField($indexProperty);
                        }
                    }

                    if (!$collectionKeyType = $this->getPhpType($typeOfField)) {
                        return null;
                    }
                }
            }

            return [new Type(
                Type::BUILTIN_TYPE_OBJECT,
                false,
                'Doctrine\Common\Collections\Collection',
                true,
                new Type($collectionKeyType),
                new Type(Type::BUILTIN_TYPE_OBJECT, false, $class)
            )];
        }

        if ($metadata instanceof ClassMetadataInfo && class_exists('Doctrine\ORM\Mapping\Embedded') && isset($metadata->embeddedClasses[$property])) {
            return [new Type(Type::BUILTIN_TYPE_OBJECT, false, $metadata->embeddedClasses[$property]['class'])];
        }

        if ($metadata->hasField($property)) {
            $typeOfField = $metadata->getTypeOfField($property);

            if (!$builtinType = $this->getPhpType($typeOfField)) {
                return null;
            }

            $nullable = $metadata instanceof ClassMetadataInfo && $metadata->isNullable($property);

            switch ($builtinType) {
                case Type::BUILTIN_TYPE_OBJECT:
                    switch ($typeOfField) {
                        case self::$useDeprecatedConstants ? DBALType::DATE : Types::DATE_MUTABLE:
                        // no break
                        case self::$useDeprecatedConstants ? DBALType::DATETIME : Types::DATETIME_MUTABLE:
                        // no break
                        case self::$useDeprecatedConstants ? DBALType::DATETIMETZ : Types::DATETIMETZ_MUTABLE:
                        // no break
                        case 'vardatetime':
                        case self::$useDeprecatedConstants ? DBALType::TIME : Types::TIME_MUTABLE:
                            return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, 'DateTime')];

                        case 'date_immutable':
                        case 'datetime_immutable':
                        case 'datetimetz_immutable':
                        case 'time_immutable':
                            return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, 'DateTimeImmutable')];

                        case 'dateinterval':
                            return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, 'DateInterval')];
                    }

                    break;
                case Type::BUILTIN_TYPE_ARRAY:
                    switch ($typeOfField) {
                        case self::$useDeprecatedConstants ? DBALType::TARRAY : 'array':
                        // no break
                        case 'json_array':
                            return [new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true)];

                        case self::$useDeprecatedConstants ? DBALType::SIMPLE_ARRAY : Types::SIMPLE_ARRAY:
                            return [new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))];
                    }
            }

            return [new Type($builtinType, $nullable)];
        }

        return null;
    }

    /**
     * Determines whether an association is nullable.
     *
     * @return bool
     *
     * @see https://github.com/doctrine/doctrine2/blob/v2.5.4/lib/Doctrine/ORM/Tools/EntityGenerator.php#L1221-L1246
     */
    private function isAssociationNullable(array $associationMapping)
    {
        if (isset($associationMapping['id']) && $associationMapping['id']) {
            return false;
        }

        if (!isset($associationMapping['joinColumns'])) {
            return true;
        }

        $joinColumns = $associationMapping['joinColumns'];
        foreach ($joinColumns as $joinColumn) {
            if (isset($joinColumn['nullable']) && !$joinColumn['nullable']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets the corresponding built-in PHP type.
     *
     * @param string $doctrineType
     *
     * @return string|null
     */
    private function getPhpType($doctrineType)
    {
        switch ($doctrineType) {
            case self::$useDeprecatedConstants ? DBALType::SMALLINT : Types::SMALLINT:
            // no break
            case self::$useDeprecatedConstants ? DBALType::INTEGER : Types::INTEGER:
                return Type::BUILTIN_TYPE_INT;

            case self::$useDeprecatedConstants ? DBALType::FLOAT : Types::FLOAT:
                return Type::BUILTIN_TYPE_FLOAT;

            case self::$useDeprecatedConstants ? DBALType::BIGINT : Types::BIGINT:
            // no break
            case self::$useDeprecatedConstants ? DBALType::STRING : Types::STRING:
            // no break
            case self::$useDeprecatedConstants ? DBALType::TEXT : Types::TEXT:
            // no break
            case self::$useDeprecatedConstants ? DBALType::GUID : Types::GUID:
            // no break
            case self::$useDeprecatedConstants ? DBALType::DECIMAL : Types::DECIMAL:
                return Type::BUILTIN_TYPE_STRING;

            case self::$useDeprecatedConstants ? DBALType::BOOLEAN : Types::BOOLEAN:
                return Type::BUILTIN_TYPE_BOOL;

            case self::$useDeprecatedConstants ? DBALType::BLOB : Types::BLOB:
            // no break
            case 'binary':
                return Type::BUILTIN_TYPE_RESOURCE;

            case self::$useDeprecatedConstants ? DBALType::OBJECT : Types::OBJECT:
            // no break
            case self::$useDeprecatedConstants ? DBALType::DATE : Types::DATE_MUTABLE:
            // no break
            case self::$useDeprecatedConstants ? DBALType::DATETIME : Types::DATETIME_MUTABLE:
            // no break
            case self::$useDeprecatedConstants ? DBALType::DATETIMETZ : Types::DATETIMETZ_MUTABLE:
            // no break
            case 'vardatetime':
            case self::$useDeprecatedConstants ? DBALType::TIME : Types::TIME_MUTABLE:
            // no break
            case 'date_immutable':
            case 'datetime_immutable':
            case 'datetimetz_immutable':
            case 'time_immutable':
            case 'dateinterval':
                return Type::BUILTIN_TYPE_OBJECT;

            case self::$useDeprecatedConstants ? DBALType::TARRAY : 'array':
            // no break
            case self::$useDeprecatedConstants ? DBALType::SIMPLE_ARRAY : Types::SIMPLE_ARRAY:
            // no break
            case 'json_array':
                return Type::BUILTIN_TYPE_ARRAY;
        }

        return null;
    }
}
