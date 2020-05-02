<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\Lazy,
    Adapter\InMemory,
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
use Innmind\BlackBox\PHPUnit\BlackBox;
use Properties\Innmind\Filesystem\Adapter;

class LazyTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $l = new Lazy($a = new InMemory);

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
        $l = new Lazy($a = new InMemory);

        $l->add(new Directory(new Name('foo')));
        $l->remove(new Name('foo'));
        $l->persist();
        $this->assertFalse($l->contains(new Name('foo')));
        $this->assertFalse($a->contains(new Name('foo')));
    }

    public function testAddUnpersistedRemovedFile()
    {
        $l = new Lazy($a = new InMemory);

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
        $l = new Lazy(new InMemory);

        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        $l->get(new Name('foo'));
    }

    public function testRemovingUnknwonFileDoesntThrow()
    {
        $l = new Lazy(new InMemory);

        $this->assertNull($l->remove(new Name('foo')));
    }

    public function testAll()
    {
        $memory = new InMemory;
        $lazy = new Lazy($memory);
        $memory->add(new File(new Name('foo'), Stream::ofContent('')));
        $lazy->remove(new Name('foo'));
        $lazy->add($bar = new File(new Name('bar'), Stream::ofContent('')));

        $all = $lazy->all();
        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(FileInterface::class, $all->type());
        $this->assertCount(1, $all);
        $this->assertSame([$bar], unwrap($all));
    }

    public function testGetFileAddedButNotYetPersisted()
    {
        $filesystem = new Lazy(new InMemory);
        $file = new File(
            new Name('foo'),
            Stream::ofContent(''),
        );

        $this->assertNull($filesystem->add($file));
        $this->assertSame($file, $filesystem->get($file->name()));
    }

    public function testHoldProperties()
    {
        $this
            ->forAll(Adapter::properties($this->seeder()))
            ->then(function($properties) {
                $properties->ensureHeldBy(new Lazy(new InMemory));
            });
    }
}
