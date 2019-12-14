<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\LazyAdapter,
    Adapter\MemoryAdapter,
    LazyAdapter as LazyAdapterInterface,
    Directory\Directory,
    File as FileInterface,
    File\File,
    Exception\FileNotFound,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class LazyAdapterTest extends TestCase
{
    public function testInterface()
    {
        $l = new LazyAdapter($a = new MemoryAdapter);

        $this->assertInstanceOf(LazyAdapterInterface::class, $l);
        $this->assertFalse($l->contains('foo'));
        $this->assertNull(
            $l->add($d = new Directory('foo'))
        );
        $this->assertTrue($l->contains('foo'));
        $this->assertFalse($a->contains('foo'));
        $this->assertNull($l->persist());
        $this->assertTrue($l->contains('foo'));
        $this->assertTrue($a->contains('foo'));
        $this->assertNull($l->remove('foo'));
        $this->assertFalse($l->contains('foo'));
        $this->assertTrue($a->contains('foo'));
        $l->persist();
        $this->assertFalse($l->contains('foo'));
        $this->assertFalse($a->contains('foo'));
    }

    public function testRemoveUnpersistedAddedFile()
    {
        $l = new LazyAdapter($a = new MemoryAdapter);

        $l->add(new Directory('foo'));
        $l->remove('foo');
        $l->persist();
        $this->assertFalse($l->contains('foo'));
        $this->assertFalse($a->contains('foo'));
    }

    public function testAddUnpersistedRemovedFile()
    {
        $l = new LazyAdapter($a = new MemoryAdapter);

        $a->add(new Directory('foo'));
        $l->remove('foo');
        $l->add($d = new Directory('foo'));
        $l->persist();
        $this->assertTrue($l->contains('foo'));
        $this->assertTrue($a->contains('foo'));
        $this->assertSame($d, $l->get('foo'));
        $this->assertSame($d, $a->get('foo'));
    }

    public function testThrowWhenGettingUnknwonFile()
    {
        $l = new LazyAdapter(new MemoryAdapter);

        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        $l->get('foo');
    }

    public function testRemovingUnknwonFileDoesntThrow()
    {
        $l = new LazyAdapter(new MemoryAdapter);

        $this->assertNull($l->remove('foo'));
    }

    public function testAll()
    {
        $memory = new MemoryAdapter;
        $lazy = new LazyAdapter($memory);
        $memory->add(new File('foo', Stream::ofContent('')));
        $lazy->remove('foo');
        $lazy->add($bar = new File('bar', Stream::ofContent('')));

        $all = $lazy->all();
        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(FileInterface::class, $all->type());
        $this->assertCount(1, $all);
        $this->assertSame([$bar], unwrap($all));
    }
}
