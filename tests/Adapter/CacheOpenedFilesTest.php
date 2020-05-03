<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\CacheOpenedFiles,
    Adapter\InMemory,
    Adapter,
    File,
    Name,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Properties\Innmind\Filesystem\Adapter as PAdapter;

class CacheOpenedFilesTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Adapter::class,
            new CacheOpenedFiles(
                $this->createMock(Adapter::class)
            )
        );
    }

    public function testAdd()
    {
        $filesystem = new CacheOpenedFiles(
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
        $filesystem = new CacheOpenedFiles(
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
        $filesystem = new CacheOpenedFiles(
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
        $filesystem = new CacheOpenedFiles(
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
        $filesystem = new CacheOpenedFiles(
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
        $filesystem = new CacheOpenedFiles(
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

    public function testHoldProperties()
    {
        $this
            ->forAll(PAdapter::properties())
            ->then(function($properties) {
                $properties->ensureHeldBy(new CacheOpenedFiles(new InMemory));
            });
    }
}
