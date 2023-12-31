<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyInfo;

/**
 * Type value object (immutable).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @final since version 3.3
 */
class Type
{
    final const BUILTIN_TYPE_INT = 'int';

    final const BUILTIN_TYPE_FLOAT = 'float';

    final const BUILTIN_TYPE_STRING = 'string';

    final const BUILTIN_TYPE_BOOL = 'bool';

    final const BUILTIN_TYPE_RESOURCE = 'resource';

    final const BUILTIN_TYPE_OBJECT = 'object';

    final const BUILTIN_TYPE_ARRAY = 'array';

    final const BUILTIN_TYPE_NULL = 'null';

    final const BUILTIN_TYPE_CALLABLE = 'callable';

    final const BUILTIN_TYPE_ITERABLE = 'iterable';

    /**
     * List of PHP builtin types.
     *
     * @var string[]
     */
    public static $builtinTypes = [
        self::BUILTIN_TYPE_INT,
        self::BUILTIN_TYPE_FLOAT,
        self::BUILTIN_TYPE_STRING,
        self::BUILTIN_TYPE_BOOL,
        self::BUILTIN_TYPE_RESOURCE,
        self::BUILTIN_TYPE_OBJECT,
        self::BUILTIN_TYPE_ARRAY,
        self::BUILTIN_TYPE_CALLABLE,
        self::BUILTIN_TYPE_NULL,
        self::BUILTIN_TYPE_ITERABLE,
    ];

    private $builtinType;

    private $nullable;

    private $class;

    private $collection;

    private ?\Symfony\Component\PropertyInfo\Type $collectionKeyType = null;

    private ?\Symfony\Component\PropertyInfo\Type $collectionValueType = null;

    /**
     * @param string      $builtinType
     * @param bool        $nullable
     * @param string|null $class
     * @param bool        $collection
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($builtinType, $nullable = false, $class = null, $collection = false, self $collectionKeyType = null, self $collectionValueType = null)
    {
        if (!\in_array($builtinType, self::$builtinTypes)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid PHP type.', $builtinType));
        }

        $this->builtinType = $builtinType;
        $this->nullable = $nullable;
        $this->class = $class;
        $this->collection = $collection;
        $this->collectionKeyType = $collectionKeyType;
        $this->collectionValueType = $collectionValueType;
    }

    /**
     * Gets built-in type.
     *
     * Can be bool, int, float, string, array, object, resource, null, callback or iterable.
     *
     * @return string
     */
    public function getBuiltinType()
    {
        return $this->builtinType;
    }

    /**
     * Allows null value?
     *
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * Gets the class name.
     *
     * Only applicable if the built-in type is object.
     *
     * @return string|null
     */
    public function getClassName()
    {
        return $this->class;
    }

    /**
     * Is collection?
     *
     * @return bool
     */
    public function isCollection()
    {
        return $this->collection;
    }

    /**
     * Gets collection key type.
     *
     * Only applicable for a collection type.
     *
     */
    public function getCollectionKeyType(): ?\Symfony\Component\PropertyInfo\Type
    {
        return $this->collectionKeyType;
    }

    /**
     * Gets collection value type.
     *
     * Only applicable for a collection type.
     *
     */
    public function getCollectionValueType(): ?\Symfony\Component\PropertyInfo\Type
    {
        return $this->collectionValueType;
    }
}
