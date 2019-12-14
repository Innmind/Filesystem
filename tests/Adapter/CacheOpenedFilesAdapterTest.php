<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\CacheOpenedFilesAdapter,
    Adapter,
    File,
    Name\Name
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CacheOpenedFilesAdapterTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Adapter::class,
            new CacheOpenedFilesAdapter(
                $this->createMock(Adapter::class)
            )
        );
    }

    public function testAdd()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $file = $this->createMock(File::class);
        $file
            ->method('name')
            ->willReturn(new Name('foo'));
        $inner
            ->expects($this->once())
            ->method('add')
            ->with($file);

        $this->assertNull($filesystem->add($file));
        $this->assertSame($file, $filesystem->get('foo'));
    }

    public function testGet()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $file = $this->createMock(File::class);
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

    public function testContainsFromInnerAdapter()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $inner
            ->expects($this->once())
            ->method('contains')
            ->with('foo')
            ->willReturn(true);

        $this->assertTrue($filesystem->contains('foo'));
    }

    public function testContainsFromCache()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $inner
            ->expects($this->never())
            ->method('contains');
        $file = $this->createMock(File::class);
        $file
            ->method('name')
            ->willReturn(new Name('foo'));
        $filesystem->add($file);

        $this->assertTrue($filesystem->contains('foo'));
    }

    public function testRemove()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $file = $this->createMock(File::class);
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
            ->willReturn($expected = $this->createMock(File::class));
        $filesystem->add($file);

        $this->assertNull($filesystem->remove('foo'));
        $this->assertSame($expected, $filesystem->get('foo'));
    }

    public function testAll()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $file = $this->createMock(File::class);
        $file
            ->method('name')
            ->willReturn(new Name('foo'));
        $inner
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                $expected = Map::of('string', File::class)
                    ->put('foo', $file)
            );

        $this->assertSame($expected, $filesystem->all());
        $this->assertSame($file, $filesystem->get('foo'));
    }
}
