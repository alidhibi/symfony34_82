<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Tests\Session\Storage\Handler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Test class for NativeFileSessionHandler.
 *
 * @author Drak <drak@zikula.org>
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class NativeFileSessionHandlerTest extends TestCase
{
    public function testConstruct(): void
    {
        $storage = new NativeSessionStorage(['name' => 'TESTING'], new NativeFileSessionHandler(sys_get_temp_dir()));

        $this->assertEquals('files', $storage->getSaveHandler()->getSaveHandlerName());
        $this->assertEquals('user', ini_get('session.save_handler'));

        $this->assertEquals(sys_get_temp_dir(), ini_get('session.save_path'));
        $this->assertEquals('TESTING', ini_get('session.name'));
    }

    /**
     * @dataProvider savePathDataProvider
     */
    public function testConstructSavePath(string $savePath, string $expectedSavePath, string $path): void
    {
        new NativeFileSessionHandler($savePath);
        $this->assertEquals($expectedSavePath, ini_get('session.save_path'));
        $this->assertDirectoryExists(realpath($path));

        rmdir($path);
    }

    public function savePathDataProvider(): array
    {
        $base = sys_get_temp_dir();

        return [
            [$base . '/foo', $base . '/foo', $base . '/foo'],
            [sprintf('5;%s/foo', $base), sprintf('5;%s/foo', $base), $base . '/foo'],
            [sprintf('5;0600;%s/foo', $base), sprintf('5;0600;%s/foo', $base), $base . '/foo'],
        ];
    }

    public function testConstructException(): void
    {
        $this->expectException('InvalidArgumentException');
        new NativeFileSessionHandler('something;invalid;with;too-many-args');
    }

    public function testConstructDefault(): void
    {
        $path = ini_get('session.save_path');
        new NativeSessionStorage(['name' => 'TESTING'], new NativeFileSessionHandler());

        $this->assertEquals($path, ini_get('session.save_path'));
    }
}
