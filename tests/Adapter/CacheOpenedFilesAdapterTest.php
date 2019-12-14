<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\CacheOpenedFilesAdapter,
    Adapter,
    File,
    Name,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Set;
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
        $this->assertSame($file, $filesystem->get(new Name('foo')));
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
            ->with(new Name('foo'))
            ->willReturn($file);

        $this->assertSame($file, $filesystem->get(new Name('foo')));
        $this->assertSame($file, $filesystem->get(new Name('foo')));
    }

    public function testContainsFromInnerAdapter()
    {
        $filesystem = new CacheOpenedFilesAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $inner
            ->expects($this->once())
            ->method('contains')
            ->with(new Name('foo'))
            ->willReturn(true);

        $this->assertTrue($filesystem->contains(new Name('foo')));
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

        $this->assertTrue($filesystem->contains(new Name('foo')));
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
            ->with(new Name('foo'));
        $inner
            ->expects($this->once())
            ->method('get')
            ->with(new Name('foo'))
            ->willReturn($expected = new File\File(
                new Name('foo'),
                $this->createMock(Readable::class),
            ));
        $filesystem->add($file);

        $this->assertNull($filesystem->remove(new Name('foo')));
        $this->assertSame($expected, $filesystem->get(new Name('foo')));
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
                $expected = Set::of(File::class, $file)
            );

        $this->assertSame($expected, $filesystem->all());
        $this->assertSame($file, $filesystem->get(new Name('foo')));
    }
}
