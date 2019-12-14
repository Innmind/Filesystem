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
    Name,
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
        $this->assertFalse($l->contains(new Name('foo')));
        $this->assertNull(
            $l->add($d = new Directory(new Name('foo')))
        );
        $this->assertTrue($l->contains(new Name('foo')));
        $this->assertFalse($a->contains(new Name('foo')));
        $this->assertNull($l->persist());
        $this->assertTrue($l->contains(new Name('foo')));
        $this->assertTrue($a->contains(new Name('foo')));
        $this->assertNull($l->remove(new Name('foo')));
        $this->assertFalse($l->contains(new Name('foo')));
        $this->assertTrue($a->contains(new Name('foo')));
        $l->persist();
        $this->assertFalse($l->contains(new Name('foo')));
        $this->assertFalse($a->contains(new Name('foo')));
    }

    public function testRemoveUnpersistedAddedFile()
    {
        $l = new LazyAdapter($a = new MemoryAdapter);

        $l->add(new Directory(new Name('foo')));
        $l->remove(new Name('foo'));
        $l->persist();
        $this->assertFalse($l->contains(new Name('foo')));
        $this->assertFalse($a->contains(new Name('foo')));
    }

    public function testAddUnpersistedRemovedFile()
    {
        $l = new LazyAdapter($a = new MemoryAdapter);

        $a->add(new Directory(new Name('foo')));
        $l->remove(new Name('foo'));
        $l->add($d = new Directory(new Name('foo')));
        $l->persist();
        $this->assertTrue($l->contains(new Name('foo')));
        $this->assertTrue($a->contains(new Name('foo')));
        $this->assertSame($d, $l->get(new Name('foo')));
        $this->assertSame($d, $a->get(new Name('foo')));
    }

    public function testThrowWhenGettingUnknwonFile()
    {
        $l = new LazyAdapter(new MemoryAdapter);

        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        $l->get(new Name('foo'));
    }

    public function testRemovingUnknwonFileDoesntThrow()
    {
        $l = new LazyAdapter(new MemoryAdapter);

        $this->assertNull($l->remove(new Name('foo')));
    }

    public function testAll()
    {
        $memory = new MemoryAdapter;
        $lazy = new LazyAdapter($memory);
        $memory->add(new File(new Name('foo'), Stream::ofContent('')));
        $lazy->remove(new Name('foo'));
        $lazy->add($bar = new File(new Name('bar'), Stream::ofContent('')));

        $all = $lazy->all();
        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(FileInterface::class, $all->type());
        $this->assertCount(1, $all);
        $this->assertSame([$bar], unwrap($all));
    }
}
