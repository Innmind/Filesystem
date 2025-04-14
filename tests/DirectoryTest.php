<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests;

use Innmind\Filesystem\{
    Directory,
    File,
    Name,
    File\Content,
    Exception\DuplicatedFile,
};
use Innmind\Immutable\{
    Set,
    Sequence,
    SideEffect,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
};
use Fixtures\Innmind\Filesystem\Name as FName;

class DirectoryTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $d = Directory::of(Name::of('foo'));

        $this->assertSame('foo', $d->name()->toString());
    }

    public function testNamed()
    {
        $directory = Directory::named('foo');

        $this->assertInstanceOf(Directory::class, $directory);
        $this->assertSame('foo', $directory->name()->toString());
    }

    public function testAdd()
    {
        $d = Directory::of(Name::of('foo'));

        $d2 = $d->add(
            $file = File::of(Name::of('foo'), Content::ofString('bar')),
        );

        $this->assertInstanceOf(Directory::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame($d->name(), $d2->name());
        $this->assertSame(0, $d->removed()->count());
        $this->assertSame(0, $d2->removed()->count());
        $this->assertFalse($d->contains($file->name()));
        $this->assertTrue($d2->contains($file->name()));
        $this->assertSame($file, $d2->get($file->name())->match(
            static fn($file) => $file,
            static fn() => null,
        ));
    }

    public function testGet()
    {
        $d = Directory::of(Name::of('foo'))
            ->add($f = File::of(Name::of('bar'), Content::ofString('baz')));

        $this->assertSame(
            $f,
            $d->get(Name::of('bar'))->match(
                static fn($file) => $file,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenGettingUnknownFile()
    {
        $this->assertNull(
            Directory::of(Name::of('foo'))
                ->get(Name::of('bar'))
                ->match(
                    static fn($file) => $file,
                    static fn() => null,
                ),
        );
    }

    public function testContains()
    {
        $d = Directory::of(Name::of('foo'))
            ->add(File::of(Name::of('bar'), Content::ofString('baz')));

        $this->assertFalse($d->contains(Name::of('baz')));
        $this->assertTrue($d->contains(Name::of('bar')));
    }

    public function testRemove()
    {
        $d = Directory::of(Name::of('foo'))
            ->add(File::of(Name::of('bar'), Content::ofString('baz')));

        $d2 = $d->remove(Name::of('bar'));

        $this->assertInstanceOf(Directory::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame($d->name(), $d2->name());
        $this->assertSame(0, $d->removed()->count());
        $this->assertSame(1, $d2->removed()->count());
        $this->assertSame(
            'bar',
            $d2
                ->removed()
                ->find(static fn() => true)
                ->match(
                    static fn($name) => $name->toString(),
                    static fn() => null,
                ),
        );
    }

    public function testForeach()
    {
        $directory = Directory::of(
            Name::of('foo'),
            Sequence::lazy(static function() {
                yield File::of(Name::of('foo'), Content::ofString('foo'));
                yield File::of(Name::of('bar'), Content::ofString('bar'));
                yield File::of(Name::of('foobar'), Content::ofString('foobar'));
                yield Directory::of(Name::of('sub'));
            }),
        );

        $called = 0;
        $this->assertInstanceOf(
            SideEffect::class,
            $directory->foreach(static function() use (&$called) {
                ++$called;
            }),
        );
        $this->assertSame(4, $called);
    }

    public function testReduce()
    {
        $directory = Directory::of(
            Name::of('foo'),
            Sequence::lazy(static function() {
                yield File::of(Name::of('foo'), Content::ofString('foo'));
                yield File::of(Name::of('bar'), Content::ofString('bar'));
                yield File::of(Name::of('foobar'), Content::ofString('foobar'));
                yield Directory::of(Name::of('sub'));
            }),
        );

        $reduced = $directory->reduce(
            '',
            static fn($carry, $file) => $carry.$file->name()->toString(),
        );

        $this->assertSame('foobarfoobarsub', $reduced);
    }

    public function testFilter()
    {
        $directory = Directory::of(
            Name::of('foo'),
            Sequence::lazy(static function() {
                yield File::of(Name::of('foo'), Content::ofString('foo'));
                yield File::of(Name::of('bar'), Content::ofString('bar'));
                yield File::of(Name::of('foobar'), Content::ofString('foobar'));
                yield Directory::of(Name::of('sub'));
            }),
        );

        $filtered = $directory->filter(
            static fn($file) => \strpos($file->name()->toString(), 'foo') === 0,
        );

        $this->assertInstanceOf(Directory::class, $filtered);
        $files = $filtered->reduce(
            Set::objects(),
            static fn($files, $file) => ($files)($file),
        );
        $this->assertSame('foo', $files->toList()[0]->name()->toString());
        $this->assertSame('foobar', $files->toList()[1]->name()->toString());
    }

    public function testDirectoryLoadedWithDifferentFilesWithTheSameNameThrows()
    {
        $this
            ->forAll(
                FName::any(),
                FName::any(),
            )
            ->then(function($directory, $file) {
                $this->expectException(DuplicatedFile::class);
                $this->expectExceptionMessage("Same file '{$file->toString()}' found multiple times");

                Directory::of(
                    $directory,
                    Sequence::of(
                        File::named($file->toString(), Content::none()),
                        File::named($file->toString(), Content::none()),
                    ),
                );
            });
    }

    public function testNamedDirectoryLoadedWithDifferentFilesWithTheSameNameThrows()
    {
        $this
            ->forAll(
                FName::any(),
                FName::any(),
            )
            ->then(function($directory, $file) {
                $this->expectException(DuplicatedFile::class);
                $this->expectExceptionMessage("Same file '{$file->toString()}' found multiple times");

                Directory::named(
                    $directory->toString(),
                    Sequence::of(
                        File::named($file->toString(), Content::none()),
                        File::named($file->toString(), Content::none()),
                    ),
                );
            });
    }

    public function testLazyLoadingADirectoryDoesntLoadFiles()
    {
        $this
            ->forAll(FName::any())
            ->then(function($name) {
                $this->assertInstanceOf(
                    Directory::class,
                    Directory::lazy(
                        $name,
                        Sequence::lazy(static function() {
                            throw new \Exception;

                            yield false;
                        }),
                    ),
                );
            });
    }
}
