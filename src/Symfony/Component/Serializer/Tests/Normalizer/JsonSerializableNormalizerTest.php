<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Normalizer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Tests\Fixtures\JsonSerializableDummy;

/**
 * @author Fred Cox <mcfedr@gmail.com>
 */
class JsonSerializableNormalizerTest extends TestCase
{
    private \Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer $normalizer;

    /**
     * @var MockObject|SerializerInterface
     */
    private $serializer;

    protected function setUp()
    {
        $this->serializer = $this->getMockBuilder(JsonSerializerNormalizer::class)->getMock();
        $this->normalizer = new JsonSerializableNormalizer();
        $this->normalizer->setSerializer($this->serializer);
    }

    public function testSupportNormalization(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization(new JsonSerializableDummy()));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalize(): void
    {
        $this->serializer
            ->expects($this->once())
            ->method('normalize')
            ->willReturnCallback(function ($data): string {
                $this->assertSame(['foo' => 'a', 'bar' => 'b', 'baz' => 'c'], array_diff_key($data, ['qux' => '']));

                return 'string_object';
            })
        ;

        $this->assertEquals('string_object', $this->normalizer->normalize(new JsonSerializableDummy()));
    }

    public function testCircularNormalize(): void
    {
        $this->expectException(\Symfony\Component\Serializer\Exception\CircularReferenceException::class);
        $this->normalizer->setCircularReferenceLimit(1);

        $this->serializer
            ->expects($this->once())
            ->method('normalize')
            ->willReturnCallback(function (array $data, $format, $context): string {
                $this->normalizer->normalize($data['qux'], $format, $context);

                return 'string_object';
            })
        ;

        $this->assertEquals('string_object', $this->normalizer->normalize(new JsonSerializableDummy()));
    }

    public function testInvalidDataThrowException(): void
    {
        $this->expectException(\Symfony\Component\Serializer\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The object must implement "JsonSerializable".');
        $this->normalizer->normalize(new \stdClass());
    }
}

abstract class JsonSerializerNormalizer implements SerializerInterface, NormalizerInterface
{
}
