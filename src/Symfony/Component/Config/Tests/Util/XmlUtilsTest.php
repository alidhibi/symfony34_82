<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Config\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Util\XmlUtils;

class XmlUtilsTest extends TestCase
{
    public function testLoadFile(): void
    {
        $fixtures = __DIR__.'/../Fixtures/Util/';

        try {
            XmlUtils::loadFile($fixtures);
            $this->fail();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertStringContainsString('is not a file', $invalidArgumentException->getMessage());
        }

        try {
            XmlUtils::loadFile($fixtures.'non_existing.xml');
            $this->fail();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertStringContainsString('is not a file', $invalidArgumentException->getMessage());
        }

        try {
            if ('\\' === \DIRECTORY_SEPARATOR) {
                $this->markTestSkipped('chmod is not supported on Windows');
            }

            chmod($fixtures.'not_readable.xml', 000);
            XmlUtils::loadFile($fixtures.'not_readable.xml');
            $this->fail();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            chmod($fixtures.'not_readable.xml', 0644);
            $this->assertStringContainsString('is not readable', $invalidArgumentException->getMessage());
        }

        try {
            XmlUtils::loadFile($fixtures.'invalid.xml');
            $this->fail();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertStringContainsString('ERROR ', $invalidArgumentException->getMessage());
        }

        try {
            XmlUtils::loadFile($fixtures.'document_type.xml');
            $this->fail();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertStringContainsString('Document types are not allowed', $invalidArgumentException->getMessage());
        }

        try {
            XmlUtils::loadFile($fixtures.'invalid_schema.xml', $fixtures.'schema.xsd');
            $this->fail();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertStringContainsString('ERROR 1845', $invalidArgumentException->getMessage());
        }

        try {
            XmlUtils::loadFile($fixtures.'invalid_schema.xml', 'invalid_callback_or_file');
            $this->fail();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertStringContainsString('XSD file or callable', $invalidArgumentException->getMessage());
        }

        $mock = $this->getMockBuilder(Validator::class)->getMock();
        $mock->expects($this->exactly(2))->method('validate')->will($this->onConsecutiveCalls(false, true));

        try {
            XmlUtils::loadFile($fixtures.'valid.xml', [$mock, 'validate']);
            $this->fail();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertMatchesRegularExpression('/The XML file ".+" is not valid\./', $invalidArgumentException->getMessage());
        }

        $this->assertInstanceOf('DOMDocument', XmlUtils::loadFile($fixtures.'valid.xml', [$mock, 'validate']));
        $this->assertSame([], libxml_get_errors());
    }

    public function testParseWithInvalidValidatorCallable(): void
    {
        $this->expectException(\Symfony\Component\Config\Util\Exception\InvalidXmlException::class);
        $this->expectExceptionMessage('The XML is not valid');
        $fixtures = __DIR__.'/../Fixtures/Util/';

        $mock = $this->getMockBuilder(Validator::class)->getMock();
        $mock->expects($this->once())->method('validate')->willReturn(false);

        XmlUtils::parse(file_get_contents($fixtures.'valid.xml'), [$mock, 'validate']);
    }

    public function testLoadFileWithInternalErrorsEnabled(): void
    {
        $internalErrors = libxml_use_internal_errors(true);

        $this->assertSame([], libxml_get_errors());
        $this->assertInstanceOf('DOMDocument', XmlUtils::loadFile(__DIR__.'/../Fixtures/Util/invalid_schema.xml'));
        $this->assertSame([], libxml_get_errors());

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
    }

    /**
     * @dataProvider getDataForConvertDomToArray
     */
    public function testConvertDomToArray(string|array|null $expected, string $xml, bool $root = false, bool $checkPrefix = true): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML($root ? $xml : '<root>'.$xml.'</root>');

        $this->assertSame($expected, XmlUtils::convertDomElementToArray($dom->documentElement, $checkPrefix));
    }

    public function getDataForConvertDomToArray(): array
    {
        return [
            [null, ''],
            ['bar', 'bar'],
            [['bar' => 'foobar'], '<foo bar="foobar" />', true],
            [['foo' => null], '<foo />'],
            [['foo' => 'bar'], '<foo>bar</foo>'],
            [['foo' => ['foo' => 'bar']], '<foo foo="bar"/>'],
            [['foo' => ['foo' => 0]], '<foo><foo>0</foo></foo>'],
            [['foo' => ['foo' => 'bar']], '<foo><foo>bar</foo></foo>'],
            [['foo' => ['foo' => 'bar', 'value' => 'text']], '<foo foo="bar">text</foo>'],
            [['foo' => ['attr' => 'bar', 'foo' => 'text']], '<foo attr="bar"><foo>text</foo></foo>'],
            [['foo' => ['bar', 'text']], '<foo>bar</foo><foo>text</foo>'],
            [['foo' => [['foo' => 'bar'], ['foo' => 'text']]], '<foo foo="bar"/><foo foo="text" />'],
            [['foo' => ['foo' => ['bar', 'text']]], '<foo foo="bar"><foo>text</foo></foo>'],
            [['foo' => 'bar'], '<foo><!-- Comment -->bar</foo>'],
            [['foo' => 'text'], '<foo xmlns:h="http://www.example.org/bar" h:bar="bar">text</foo>'],
            [['foo' => ['bar' => 'bar', 'value' => 'text']], '<foo xmlns:h="http://www.example.org/bar" h:bar="bar">text</foo>', false, false],
            [['attr' => 1, 'b' => 'hello'], '<foo:a xmlns:foo="http://www.example.org/foo" xmlns:h="http://www.example.org/bar" attr="1" h:bar="bar"><foo:b>hello</foo:b><h:c>2</h:c></foo:a>', true],
        ];
    }

    /**
     * @dataProvider getDataForPhpize
     */
    public function testPhpize(string|bool|int|float|null $expected, string $value): void
    {
        $this->assertSame($expected, XmlUtils::phpize($value));
    }

    public function getDataForPhpize(): array
    {
        return [
            ['', ''],
            [null, 'null'],
            [true, 'true'],
            [false, 'false'],
            [null, 'Null'],
            [true, 'True'],
            [false, 'False'],
            [0, '0'],
            [1, '1'],
            [-1, '-1'],
            [0777, '0777'],
            [255, '0xFF'],
            [100.0, '1e2'],
            [-120.0, '-1.2E2'],
            [-10100.1, '-10100.1'],
            ['-10,100.1', '-10,100.1'],
            ['1234 5678 9101 1121 3141', '1234 5678 9101 1121 3141'],
            ['1,2,3,4', '1,2,3,4'],
            ['11,22,33,44', '11,22,33,44'],
            ['11,222,333,4', '11,222,333,4'],
            ['1,222,333,444', '1,222,333,444'],
            ['11,222,333,444', '11,222,333,444'],
            ['111,222,333,444', '111,222,333,444'],
            ['1111,2222,3333,4444,5555', '1111,2222,3333,4444,5555'],
            ['foo', 'foo'],
            [6, '0b0110'],
        ];
    }

    public function testLoadEmptyXmlFile(): void
    {
        $file = __DIR__.'/../Fixtures/foo.xml';

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(sprintf('File "%s" does not contain valid XML, it is empty.', $file));

        XmlUtils::loadFile($file);
    }

    // test for issue https://github.com/symfony/symfony/issues/9731
    public function testLoadWrongEmptyXMLWithErrorHandler(): void
    {
        if (\LIBXML_VERSION < 20900) {
            $originalDisableEntities = libxml_disable_entity_loader(false);
        }

        $errorReporting = error_reporting(-1);

        set_error_handler(static function ($errno, $errstr) : never {
            throw new \Exception($errstr, $errno);
        });

        $file = __DIR__.'/../Fixtures/foo.xml';
        try {
            try {
                XmlUtils::loadFile($file);
                $this->fail('An exception should have been raised');
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals(sprintf('File "%s" does not contain valid XML, it is empty.', $file), $e->getMessage());
            }
        } finally {
            restore_error_handler();
            error_reporting($errorReporting);
        }

        if (\LIBXML_VERSION < 20900) {
            $disableEntities = libxml_disable_entity_loader(true);
            libxml_disable_entity_loader($disableEntities);

            libxml_disable_entity_loader($originalDisableEntities);
            $this->assertFalse($disableEntities);
        }

        // should not throw an exception
        XmlUtils::loadFile(__DIR__.'/../Fixtures/Util/valid.xml', __DIR__.'/../Fixtures/Util/schema.xsd');
    }
}

interface Validator
{
    public function validate();
}
