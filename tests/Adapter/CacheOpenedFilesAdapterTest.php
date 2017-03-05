<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\CacheOpenedFilesAdapter,
    AdapterInterface,
    FileInterface,
    Name
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CacheOpenedFilesAdapterTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            AdapterInterface::class,
            new CacheOpenedFilesAdapter(
                $this->createMock(AdapterInterface::class)
            )
        );
    }

    public function testAdd()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(AdapterInterface::class)
        );
        $file = $this->createMock(FileInterface::class);
        $file
            ->method('name')
            ->willReturn(new Name('foo'));
        $inner
            ->expects($this->once())
            ->method('add')
            ->with($file);

        $this->assertSame($filesystem, $filesystem->add($file));
        $this->assertSame($file, $filesystem->get('foo'));
    }

    public function testGet()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(AdapterInterface::class)
        );
        $file = $this->createMock(FileInterface::class);
        $file
            ->method('name')
            ->willReturn(new Name('foo'));
        $inner
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($file);

        $this->assertSame($file, $filesystem->get('foo'));
        $this->assertSame($file, $filesystem->get('foo'));
    }

    public function testHasFromInnerAdapter()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(AdapterInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('has')
            ->with('foo')
            ->willReturn(true);

        $this->assertTrue($filesystem->has('foo'));
    }

    public function testHasFromCache()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(AdapterInterface::class)
        );
        $inner
            ->expects($this->never())
            ->method('has');
        $file = $this->createMock(FileInterface::class);
        $file
            ->method('name')
            ->willReturn(new Name('foo'));
        $filesystem->add($file);

        $this->assertTrue($filesystem->has('foo'));
    }

    public function testRemove()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(AdapterInterface::class)
        );
        $file = $this->createMock(FileInterface::class);
        $file
            ->method('name')
            ->willReturn(new Name('foo'));
        $inner
            ->expects($this->once())
            ->method('remove')
            ->with('foo');
        $inner
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($expected = $this->createMock(FileInterface::class));
        $filesystem->add($file);

        $this->assertSame($filesystem, $filesystem->remove('foo'));
        $this->assertSame($expected, $filesystem->get('foo'));
    }

    public function testAll()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(AdapterInterface::class)
        );
        $file = $this->createMock(FileInterface::class);
        $file
            ->method('name')
            ->willReturn(new Name('foo'));
        $inner
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                $expected = (new Map('string', FileInterface::class))
                    ->put('foo', $file)
            );

        $this->assertSame($expected, $filesystem->all());
        $this->assertSame($file, $filesystem->get('foo'));
    }
}
