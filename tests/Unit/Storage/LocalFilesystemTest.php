<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\Storage\Exception;
use Tubee\Storage\LocalFilesystem;

class LocalFilesystemTest extends TestCase
{
    protected $storage;

    public function setUp()
    {
        file_put_contents(__DIR__.'/Mock/bar.csv', 'bar;bar');
        file_put_contents(__DIR__.'/Mock/foo.csv', 'foo;foo');
        $this->storage = new LocalFilesystem(__DIR__.'/Mock', $this->createMock(LoggerInterface::class));
    }

    public function testRootNotFound()
    {
        $this->expectException(Exception\RootDirectoryNotFound::class);
        new LocalFilesystem(__DIR__.'/foo', $this->createMock(LoggerInterface::class));
    }

    public function testGetFiles()
    {
        $files = $this->storage->getFiles('*');
        $this->assertSame([__DIR__.'/Mock/bar.csv', __DIR__.'/Mock/foo.csv'], $files);
    }

    public function testOpenReadStreams()
    {
        $streams = $this->storage->openReadStreams('*');
        $streams = iterator_to_array($streams);
        $this->assertSame('bar;bar', fread($streams[__DIR__.'/Mock/bar.csv'], 100));
        $this->assertSame('foo;foo', fread($streams[__DIR__.'/Mock/foo.csv'], 100));
    }

    public function testOpenReadStream()
    {
        $stream = $this->storage->openReadStream('bar.csv');
        $this->assertSame('bar;bar', fread($stream, 100));
    }

    public function testOpenReadStreamFailed()
    {
        $this->expectException(Exception\OpenStreamFailed::class);
        $stream = @$this->storage->openReadStream('bar');
    }

    public function testOpenWriteStream()
    {
        $stream = $this->storage->openWriteStream('bar.csv');
        $this->assertSame(7, fwrite($stream, 'foo;foo'));
    }

    public function testOpenWriteStreamFailed()
    {
        $this->expectException(Exception\OpenStreamFailed::class);
        $stream = @$this->storage->openWriteStream('bar');
    }

    public function testSyncWriteStream()
    {
        $this->assertTrue($this->storage->syncWriteStream('foo', 'bar'));
    }
}