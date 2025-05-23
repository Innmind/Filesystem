<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\InMemory,
    Adapter,
    Directory,
    File,
    File\Content,
    Name,
};
use Innmind\Immutable\{
    Sequence,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class InMemoryTest extends TestCase
{
    public function testInterface()
    {
        $a = InMemory::new();

        $this->assertInstanceOf(Adapter::class, $a);
        $this->assertFalse($a->contains(Name::of('foo')));
        $this->assertInstanceOf(
            SideEffect::class,
            $a
                ->add($d = Directory::of(Name::of('foo')))
                ->unwrap(),
        );
        $this->assertTrue($a->contains(Name::of('foo')));
        $this->assertSame(
            $d,
            $a->get(Name::of('foo'))->match(
                static fn($file) => $file,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            SideEffect::class,
            $a
                ->remove(Name::of('foo'))
                ->unwrap(),
        );
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
        $this->assertInstanceOf(
            SideEffect::class,
            InMemory::new()
                ->remove(Name::of('foo'))
                ->unwrap(),
        );
    }

    public function testRoot()
    {
        $adapter = InMemory::new();
        $adapter
            ->add($foo = File::of(
                Name::of('foo'),
                Content::ofString('foo'),
            ))
            ->unwrap();
        $adapter
            ->add($bar = File::of(
                Name::of('bar'),
                Content::ofString('bar'),
            ))
            ->unwrap();

        $all = $adapter->root()->all();
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
                File::named('baz', Content::none()),
            ),
        ))->unwrap();
        $adapter->add(Directory::of(
            Name::of('foo'),
            Sequence::of(
                Directory::of(
                    Name::of('bar'),
                    Sequence::of(File::named('baz', Content::none())),
                ),
                Directory::of(
                    Name::of('foo'),
                    Sequence::of(File::named('foo', Content::none())),
                ),
            ),
        ))->unwrap();

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
