<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Directory;

use Innmind\Filesystem\{
    Directory\Directory,
    Directory as DirectoryInterface,
    File,
    Name,
    Event\FileWasAdded,
    Event\FileWasRemoved,
    Exception\FileNotFound,
    Exception\LogicException,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
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
        $d = new Directory(new Name('foo'));

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
        $d = new Directory(new Name('foo'));
        $d->content(); //force generation of files list, to be sure it's not cloned

        $d2 = $d->add(
            $file = new File\File(new Name('foo'), Stream::ofContent('bar'))
        );

        $this->assertInstanceOf(DirectoryInterface::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame($d->name(), $d2->name());
        $this->assertNotSame($d->content(), $d2->content());
        $this->assertSame('', $d->content()->toString());
        $this->assertSame('foo', $d2->content()->toString());
        $this->assertSame(0, $d->modifications()->count());
        $this->assertSame(1, $d2->modifications()->count());
        $this->assertInstanceOf(
            FileWasAdded::class,
            $d2->modifications()->first()
        );
        $this->assertSame($file, $d2->modifications()->first()->file());
    }

    public function testGet()
    {
        $d = (new Directory(new Name('foo')))
            ->add($f = new File\File(new Name('bar'), Stream::ofContent('baz')));

        $this->assertSame($f, $d->get(new Name('bar')));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('bar');

        (new Directory(new Name('foo')))->get(new Name('bar'));
    }

    public function testContains()
    {
        $d = (new Directory(new Name('foo')))
            ->add(new File\File(new Name('bar'), Stream::ofContent('baz')));

        $this->assertFalse($d->contains(new Name('baz')));
        $this->assertTrue($d->contains(new Name('bar')));
    }

    public function testRemove()
    {
        $d = (new Directory(new Name('foo')))
            ->add(new File\File(new Name('bar'), Stream::ofContent('baz')));
        $d->content(); //force generation of files list, to be sure it's not cloned

        $d2 = $d->remove(new Name('bar'));

        $this->assertInstanceOf(DirectoryInterface::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame($d->name(), $d2->name());
        $this->assertNotSame($d->content(), $d2->content());
        $this->assertSame('bar', $d->content()->toString());
        $this->assertSame('', $d2->content()->toString());
        $this->assertSame(1, $d->modifications()->count());
        $this->assertSame(2, $d2->modifications()->count());
        $this->assertInstanceOf(
            FileWasRemoved::class,
            $d2->modifications()->get(1)
        );
        $this->assertSame('bar', $d2->modifications()->get(1)->file()->toString());
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $dir = new Directory(new Name('foo'));

        $this->assertSame($dir, $dir->remove(new Name('bar')));
    }

    public function testGenerator()
    {
        $d = new Directory(
            new Name('foo'),
            Set::defer(File::class, (static function() {
                yield new File\File(new Name('foo'), Stream::ofContent('foo'));
                yield new File\File(new Name('bar'), Stream::ofContent('bar'));
                yield new File\File(new Name('foobar'), Stream::ofContent('foobar'));
                yield new Directory(new Name('sub'));
            })()),
        );

        $this->assertSame(
            'bar' . "\n" . 'foo' . "\n" . 'foobar' . "\n" . 'sub',
            $d->content()->toString()
        );
    }

    public function testReplaceAt()
    {
        $d = (new Directory(new Name('foobar')))
            ->add(
                (new Directory(new Name('foo')))
                    ->add(
                        (new Directory(new Name('bar')))
                            ->add(
                                (new Directory(new Name('baz')))
                                    ->add(new File\File(new Name('baz.md'), Stream::ofContent('baz')))
                            )
                    )
            );

        $d2 = $d->replaceAt(
            Path::of('/foo/bar/baz'),
            new File\File(new Name('baz.md'), Stream::ofContent('updated'))
        );
        $this->assertInstanceOf(DirectoryInterface::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame(
            'baz',
            $d->get(new Name('foo'))->get(new Name('bar'))->get(new Name('baz'))->get(new Name('baz.md'))->content()->toString()
        );
        $this->assertSame(
            'updated',
            $d2->get(new Name('foo'))->get(new Name('bar'))->get(new Name('baz'))->get(new Name('baz.md'))->content()->toString()
        );
    }

    public function testReplaceAtRoot()
    {
        $d = (new Directory(new Name('foobar')))
            ->add(
                (new Directory(new Name('foo')))
                    ->add(
                        (new Directory(new Name('bar')))
                            ->add(
                                (new Directory(new Name('baz')))
                                    ->add(new File\File(new Name('baz.md'), Stream::ofContent('baz')))
                            )
                    )
            );

        $d2 = $d->replaceAt(
            Path::of('/'),
            new File\File(new Name('foo'), Stream::ofContent('updated'))
        );
        $this->assertInstanceOf(DirectoryInterface::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame(
            'baz',
            $d->get(new Name('foo'))->get(new Name('bar'))->get(new Name('baz'))->get(new Name('baz.md'))->content()->toString()
        );
        $this->assertSame(
            'updated',
            $d2->get(new Name('foo'))->content()->toString()
        );
    }

    public function testThrowWhenReplacingAtAPathThatDoesntReferenceADirectory()
    {
        $d = (new Directory(new Name('foobar')))
            ->add(
                (new Directory(new Name('foo')))
                    ->add(
                        (new Directory(new Name('bar')))
                            ->add(
                                new File\File(new Name('baz'), Stream::ofContent('baz'))
                            )
                    )
            );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Path doesn\'t reference a directory');

        $d->replaceAt(
            Path::of('/foo/bar/baz'),
            new File\File(new Name('baz.md'), Stream::ofContent('updated'))
        );
    }

    public function testForeach()
    {
        $directory = new Directory(
            new Name('foo'),
            Set::defer(File::class, (static function() {
                yield new File\File(new Name('foo'), Stream::ofContent('foo'));
                yield new File\File(new Name('bar'), Stream::ofContent('bar'));
                yield new File\File(new Name('foobar'), Stream::ofContent('foobar'));
                yield new Directory(new Name('sub'));
            })()),
        );

        $called = 0;
        $this->assertNull($directory->foreach(static function() use (&$called) {
            ++$called;
        }));
        $this->assertSame(4, $called);
    }

    public function testReduce()
    {
        $directory = new Directory(
            new Name('foo'),
            Set::defer(File::class, (static function() {
                yield new File\File(new Name('foo'), Stream::ofContent('foo'));
                yield new File\File(new Name('bar'), Stream::ofContent('bar'));
                yield new File\File(new Name('foobar'), Stream::ofContent('foobar'));
                yield new Directory(new Name('sub'));
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
        $directory = new Directory(
            new Name('foo'),
            Set::defer(File::class, (static function() {
                yield new File\File(new Name('foo'), Stream::ofContent('foo'));
                yield new File\File(new Name('bar'), Stream::ofContent('bar'));
                yield new File\File(new Name('foobar'), Stream::ofContent('foobar'));
                yield new Directory(new Name('sub'));
            })()),
        );

        $set = $directory->filter(
            static fn($file) => \strpos($file->name()->toString(), 'foo') === 0,
        );

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame(File::class, $set->type());
        $this->assertSame('foo', unwrap($set)[0]->name()->toString());
        $this->assertSame('foobar', unwrap($set)[1]->name()->toString());
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
                $directory = new Directory($name);

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
                    File::class,
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
                $directory = new Directory($name, $files);

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
                $directory = new Directory($name);

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
                    File::class,
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
                $directory = new Directory($name, $files);

                $properties->ensureHeldBy($directory);
            });
    }

    public function testDirectoryLoadedWithDifferentFilesWithTheSameNameThrows()
    {
        $this
            ->forAll(
                FName::any(),
                FName::any(),
                DataSet\Strings::any(),
                DataSet\Strings::any(),
            )
            ->then(function($directory, $file, $content1, $content2) {
                $this->expectException(LogicException::class);
                $this->expectExceptionMessage("Same file '{$file->toString()}' found multiple times");

                new Directory(
                    $directory,
                    Set::of(
                        File::class,
                        File\File::named($file->toString(), Stream::ofContent($content1)),
                        File\File::named($file->toString(), Stream::ofContent($content2)),
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
                        Set::defer(
                            File::class,
                            (function() {
                                throw new \Exception;

                                yield false;
                            })(),
                        ),
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
