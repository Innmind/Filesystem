<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\InMemory,
    Adapter,
    Directory\Directory,
    File\File,
    File\Content\Lines,
    File\Content\None,
    Name,
};
use Innmind\Immutable\{
    Set,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class InMemoryTest extends TestCase
{
    public function testInterface()
    {
        $a = InMemory::new();

        $this->assertInstanceOf(Adapter::class, $a);
        $this->assertFalse($a->contains(Name::of('foo')));
        $this->assertNull(
            $a->add($d = Directory::of(Name::of('foo'))),
        );
        $this->assertTrue($a->contains(Name::of('foo')));
        $this->assertSame(
            $d,
            $a->get(Name::of('foo'))->match(
                static fn($file) => $file,
                static fn() => null,
            ),
        );
        $this->assertNull($a->remove(Name::of('foo')));
        $this->assertFalse($a->contains(Name::of('foo')));
    }

    public function testReturnNothingWhenGettingUnknownFile()
    {
        $this->assertNull(InMemory::new()->get(Name::of('foo'))->match(
            static fn($file) => $file,
            static fn() => null,
        ));
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertNull(InMemory::new()->remove(Name::of('foo')));
    }

    public function testRoot()
    {
        $adapter = InMemory::new();
        $adapter->add($foo = File::of(
            Name::of('foo'),
            Lines::ofContent('foo'),
        ));
        $adapter->add($bar = File::of(
            Name::of('bar'),
            Lines::ofContent('bar'),
        ));

        $all = $adapter->root()->files();
        $this->assertSame(
            [$foo, $bar],
            $all->toList(),
        );
    }

    public function testEmulateFilesystem()
    {
        $adapter = InMemory::emulateFilesystem();
        $adapter->add(Directory::of(
            Name::of('foo'),
            Sequence::of(
                Directory::named('bar'),
                File::named('baz', None::of()),
            ),
        ));
        $adapter->add(Directory::of(
            Name::of('foo'),
            Sequence::of(
                Directory::of(
                    Name::of('bar'),
                    Sequence::of(File::named('baz', None::of())),
                ),
                Directory::of(
                    Name::of('foo'),
                    Sequence::of(File::named('foo', None::of())),
                ),
            ),
        ));

        $this->assertTrue(
            $adapter
                ->get(Name::of('foo'))
                ->flatMap(static fn($directory) => $directory->get(Name::of('baz')))
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
        $this->assertTrue(
            $adapter
                ->get(Name::of('foo'))
                ->flatMap(static fn($directory) => $directory->get(Name::of('bar')))
                ->flatMap(static fn($directory) => $directory->get(Name::of('baz')))
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
        $this->assertTrue(
            $adapter
                ->get(Name::of('foo'))
                ->flatMap(static fn($directory) => $directory->get(Name::of('foo')))
                ->flatMap(static fn($directory) => $directory->get(Name::of('foo')))
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
    }
}
