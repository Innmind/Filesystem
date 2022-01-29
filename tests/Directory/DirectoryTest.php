<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Directory;

use Innmind\Filesystem\{
    Directory\Directory,
    Directory as DirectoryInterface,
    File,
    Name,
    File\Content\None,
    File\Content\Lines,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Set,
    SideEffect,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set as DataSet,
};
use Fixtures\Innmind\Filesystem\{
    Name as FName,
    File as FFile,
};
use Fixtures\Innmind\Immutable\Set as FSet;
use Properties\Innmind\Filesystem\Directory as PDirectory;

class DirectoryTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $d = Directory::of(new Name('foo'));

        $this->assertInstanceOf(DirectoryInterface::class, $d);
        $this->assertSame('foo', $d->name()->toString());
        $this->assertSame('', $d->content()->toString());
        $this->assertSame('text/directory', $d->mediaType()->toString());
        $this->assertSame($d->mediaType(), $d->mediaType());
    }

    public function testNamed()
    {
        $directory = Directory::named('foo');

        $this->assertInstanceOf(Directory::class, $directory);
        $this->assertSame('foo', $directory->name()->toString());
    }

    public function testAdd()
    {
        $d = Directory::of(new Name('foo'));
        $d->content(); //force generation of files list, to be sure it's not cloned

        $d2 = $d->add(
            $file = new File\File(new Name('foo'), Lines::ofContent('bar')),
        );

        $this->assertInstanceOf(DirectoryInterface::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame($d->name(), $d2->name());
        $this->assertNotSame($d->content(), $d2->content());
        $this->assertSame(0, $d->removed()->count());
        $this->assertSame(0, $d2->removed()->count());
    }

    public function testGet()
    {
        $d = Directory::of(new Name('foo'))
            ->add($f = new File\File(new Name('bar'), Lines::ofContent('baz')));

        $this->assertSame(
            $f,
            $d->get(new Name('bar'))->match(
                static fn($file) => $file,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenGettingUnknownFile()
    {
        $this->assertNull(
            Directory::of(new Name('foo'))
                ->get(new Name('bar'))
                ->match(
                    static fn($file) => $file,
                    static fn() => null,
                ),
        );
    }

    public function testContains()
    {
        $d = Directory::of(new Name('foo'))
            ->add(new File\File(new Name('bar'), Lines::ofContent('baz')));

        $this->assertFalse($d->contains(new Name('baz')));
        $this->assertTrue($d->contains(new Name('bar')));
    }

    public function testRemove()
    {
        $d = Directory::of(new Name('foo'))
            ->add(new File\File(new Name('bar'), Lines::ofContent('baz')));
        $d->content(); //force generation of files list, to be sure it's not cloned

        $d2 = $d->remove(new Name('bar'));

        $this->assertInstanceOf(DirectoryInterface::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame($d->name(), $d2->name());
        $this->assertNotSame($d->content(), $d2->content());
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

    public function testRemovingUnknownFileDoesntThrow()
    {
        $dir = Directory::of(new Name('foo'));

        $this->assertSame($dir, $dir->remove(new Name('bar')));
    }

    public function testForeach()
    {
        $directory = Directory::of(
            new Name('foo'),
            Set::defer((static function() {
                yield new File\File(new Name('foo'), Lines::ofContent('foo'));
                yield new File\File(new Name('bar'), Lines::ofContent('bar'));
                yield new File\File(new Name('foobar'), Lines::ofContent('foobar'));
                yield Directory::of(new Name('sub'));
            })()),
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
            new Name('foo'),
            Set::defer((static function() {
                yield new File\File(new Name('foo'), Lines::ofContent('foo'));
                yield new File\File(new Name('bar'), Lines::ofContent('bar'));
                yield new File\File(new Name('foobar'), Lines::ofContent('foobar'));
                yield Directory::of(new Name('sub'));
            })()),
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
            new Name('foo'),
            Set::defer((static function() {
                yield new File\File(new Name('foo'), Lines::ofContent('foo'));
                yield new File\File(new Name('bar'), Lines::ofContent('bar'));
                yield new File\File(new Name('foobar'), Lines::ofContent('foobar'));
                yield Directory::of(new Name('sub'));
            })()),
        );

        $filtered = $directory->filter(
            static fn($file) => \strpos($file->name()->toString(), 'foo') === 0,
        );

        $this->assertInstanceOf(DirectoryInterface::class, $filtered);
        $files = $filtered->reduce(
            Set::objects(),
            static fn($files, $file) => ($files)($file),
        );
        $this->assertSame('foo', $files->toList()[0]->name()->toString());
        $this->assertSame('foobar', $files->toList()[1]->name()->toString());
    }

    /**
     * @dataProvider properties
     */
    public function testEmptyDirectoryHoldProperty($property)
    {
        $this
            ->forAll(
                $property,
                FName::any(),
            )
            ->then(function($property, $name) {
                $directory = Directory::of($name);

                if (!$property->applicableTo($directory)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy($directory);
            });
    }

    /**
     * @dataProvider properties
     */
    public function testDirectoryWithSomeFilesHoldProperty($property)
    {
        $this
            ->forAll(
                $property,
                FName::any(),
                FSet::of(
                    new DataSet\Randomize(
                        FFile::any(),
                    ),
                    DataSet\Integers::between(1, 5), // only to speed up tests
                ),
            )
            ->filter(static function($property, $name, $files) {
                // do not accept duplicated files
                return $files
                    ->groupBy(static fn($file) => $file->name()->toString())
                    ->size() === $files->size();
            })
            ->then(function($property, $name, $files) {
                $directory = Directory::of($name, $files);

                if (!$property->applicableTo($directory)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy($directory);
            });
    }

    /**
     * @group properties
     */
    public function testEmptyDirectoryHoldProperties()
    {
        $this
            ->forAll(
                PDirectory::properties(),
                FName::any(),
            )
            ->then(static function($properties, $name) {
                $directory = Directory::of($name);

                $properties->ensureHeldBy($directory);
            });
    }

    /**
     * @group properties
     */
    public function testDirectoryWithSomeFilesHoldProperties()
    {
        $this
            ->forAll(
                PDirectory::properties(),
                FName::any(),
                FSet::of(
                    new DataSet\Randomize(
                        FFile::any(),
                    ),
                    DataSet\Integers::between(1, 5), // only to speed up tests
                ),
            )
            ->filter(static function($properties, $name, $files) {
                // do not accept duplicated files
                return $files
                    ->groupBy(static fn($file) => $file->name()->toString())
                    ->size() === $files->size();
            })
            ->then(static function($properties, $name, $files) {
                $directory = Directory::of($name, $files);

                $properties->ensureHeldBy($directory);
            });
    }

    public function testDirectoryLoadedWithDifferentFilesWithTheSameNameThrows()
    {
        $this
            ->forAll(
                FName::any(),
                FName::any(),
            )
            ->then(function($directory, $file) {
                $this->expectException(LogicException::class);
                $this->expectExceptionMessage("Same file '{$file->toString()}' found multiple times");

                Directory::of(
                    $directory,
                    Set::of(
                        File\File::named($file->toString(), None::of()),
                        File\File::named($file->toString(), None::of()),
                    ),
                );
            });
    }

    public function testDeferringTheLoadingOfADirectoryDoesntLoadFiles()
    {
        $this
            ->forAll(FName::any())
            ->then(function($name) {
                $this->assertInstanceOf(
                    Directory::class,
                    Directory::defer(
                        $name,
                        Set::defer((static function() {
                            throw new \Exception;

                            yield false;
                        })()),
                    ),
                );
            });
    }

    public function properties(): iterable
    {
        foreach (PDirectory::list() as $property) {
            yield [$property];
        }
    }
}
