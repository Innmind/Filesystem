<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\Filesystem,
    Adapter,
    File\File,
    Name,
    File as FileInterface,
    Directory as DirectoryInterface,
    Directory\Directory,
    MediaType\NullMediaType,
    Exception\FileNotFound,
    Exception\PathDoesntRepresentADirectory,
    Exception\PathTooLong,
    Exception\CannotPersistClosedStream,
    Exception\LinksAreNotSupported,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use Symfony\Component\Filesystem\Filesystem as FS;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set as DataSet,
};
use Fixtures\Innmind\Filesystem\{
    Name as FName,
    File as FFile,
};
use Properties\Innmind\Filesystem\Adapter as PAdapter;

class FilesystemTest extends TestCase
{
    use BlackBox;

    public function setUp(): void
    {
        $fs = new FS;
        $fs->remove('/tmp/test');
        $fs->remove('/tmp/foo');
    }

    public function testInterface()
    {
        $adapter = new Filesystem(Path::of('/tmp/'));

        $this->assertInstanceOf(Adapter::class, $adapter);
        $this->assertFalse($adapter->contains(new Name('foo')));
        $this->assertNull($adapter->add(new Directory(new Name('foo'))));
        $this->assertTrue($adapter->contains(new Name('foo')));
        $this->assertNull($adapter->remove(new Name('foo')));
        $this->assertFalse($adapter->contains(new Name('foo')));
    }

    public function testThrowWhenPathToMountIsNotADirectory()
    {
        $this->expectException(PathDoesntRepresentADirectory::class);
        $this->expectExceptionMessage('path/to/somewhere');

        new Filesystem(Path::of('path/to/somewhere'));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        (new Filesystem(Path::of('/tmp/')))->get(new Name('foo'));
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertNull((new Filesystem(Path::of('/tmp/')))->remove(new Name('foo')));
    }

    public function testCreateNestedStructure()
    {
        $adapter = new Filesystem(Path::of('/tmp/'));

        $directory = (new Directory(new Name('foo')))
            ->add(new File(new Name('foo.md'), Stream::ofContent('# Foo')))
            ->add(
                (new Directory(new Name('bar')))
                    ->add(new File(new Name('bar.md'), Stream::ofContent('# Bar')))
            );
        $adapter->add($directory);
        $this->assertSame(
            '# Foo',
            $adapter->get(new Name('foo'))->get(new Name('foo.md'))->content()->toString()
        );
        $this->assertSame(
            '# Bar',
            $adapter->get(new Name('foo'))->get(new Name('bar'))->get(new Name('bar.md'))->content()->toString()
        );

        $adapter = new Filesystem(Path::of('/tmp/'));
        //check it's really persisted (otherwise it will throw)
        $adapter->get(new Name('foo'));
        $this->assertSame(
            '# Foo',
            $adapter->get(new Name('foo'))->get(new Name('foo.md'))->content()->toString()
        );
        $adapter->get(new Name('foo'))->get(new Name('bar'));
        $this->assertSame(
            '# Bar',
            $adapter->get(new Name('foo'))->get(new Name('bar'))->get(new Name('bar.md'))->content()->toString()
        );

        $adapter->remove(new Name('foo'));
    }

    public function testRemoveFileWhenRemovedFromFolder()
    {
        $a = new Filesystem(Path::of('/tmp/'));

        $d = new Directory(new Name('foo'));
        $d = $d->add(new File(new Name('bar'), Stream::ofContent('some content')));
        $a->add($d);
        $d = $d->remove(new Name('bar'));
        $a->add($d);
        $this->assertSame(2, $d->modifications()->count());
        $a = new Filesystem(Path::of('/tmp/'));
        $this->assertFalse($a->get(new Name('foo'))->contains(new Name('bar')));
        $a->remove(new Name('foo'));
    }

    public function testDoesntFailWhenAddindSameDirectoryTwiceThatContainsARemovedFile()
    {
        $a = new Filesystem(Path::of('/tmp/'));

        $d = new Directory(new Name('foo'));
        $d = $d->add(new File(new Name('bar'), Stream::ofContent('some content')));
        $a->add($d);
        $d = $d->remove(new Name('bar'));
        $a->add($d);
        $a->add($d);
        $this->assertSame(2, $d->modifications()->count());
        $a = new Filesystem(Path::of('/tmp/'));
        $this->assertFalse($a->get(new Name('foo'))->contains(new Name('bar')));
        $a->remove(new Name('foo'));
    }

    public function testLoadWithMediaType()
    {
        $a = new Filesystem(Path::of('/tmp/'));
        \file_put_contents(
            '/tmp/some_content.html',
            '<!DOCTYPE html><html><body><answer value="42"/></body></html>'
        );

        $this->assertSame(
            'text/html',
            $a->get(new Name('some_content.html'))->mediaType()->toString()
        );
        $a->remove(new Name('some_content.html'));
    }

    public function testAll()
    {
        $adapter = new Filesystem(Path::of('/tmp/test/'));
        $adapter->add(new File(
            new Name('foo'),
            Stream::ofContent('foo')
        ));
        \file_put_contents('/tmp/test/bar', 'bar');
        \mkdir('/tmp/test/baz');
        \file_put_contents('/tmp/test/baz/foobar', 'baz');

        $all = $adapter->all();
        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(FileInterface::class, $all->type());
        $this->assertCount(3, $all);
        $all = $all->toMapOf(
            'string',
            FileInterface::class,
            static fn($file) => yield $file->name()->toString() => $file,
        );
        $this->assertTrue($all->contains('foo'));
        $this->assertTrue($all->contains('bar'));
        $this->assertTrue($all->contains('baz'));
        $this->assertSame('foo', $adapter->get(new Name('foo'))->content()->toString());
        $this->assertSame('bar', $adapter->get(new Name('bar'))->content()->toString());
        $this->assertInstanceOf(DirectoryInterface::class, $adapter->get(new Name('baz')));
        $adapter->remove(new Name('foo'));
        $adapter->remove(new Name('bar'));
        $adapter->remove(new Name('baz'));
    }

    public function testAddingTheSameFileTwiceDoesNothing()
    {
        $adapter = new Filesystem(Path::of('/tmp/'));
        $file = new File(
            new Name('foo'),
            Stream::ofContent('foo'),
        );

        $this->assertNull($adapter->add($file));
        $this->assertNull($adapter->add($file));
    }

    /**
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(function($property) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);
                $adapter = new Filesystem(Path::of($path));

                if (!$property->applicableTo($adapter)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy($adapter);

                (new FS)->remove($path);
            });
    }

    /**
     * @group properties
     */
    public function testHoldProperties()
    {
        $this
            ->forAll(PAdapter::properties())
            ->then(static function($properties) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                $properties->ensureHeldBy(new Filesystem(Path::of($path)));

                (new FS)->remove($path);
            });
    }

    public function testPathTooLongThrowAnException()
    {
        if (\PHP_OS !== 'Darwin') {
            $this->markTestSkipped();
        }

        $path = \sys_get_temp_dir().'/innmind/filesystem/';
        (new FS)->remove($path);

        $filesystem = new Filesystem(Path::of($path));

        $this->expectException(PathTooLong::class);

        $filesystem->add(new Directory(
            new Name(\str_repeat('a', 255)),
            Set::of(
                FileInterface::class,
                new Directory(
                    new Name(\str_repeat('a', 255)),
                    Set::of(
                        FileInterface::class,
                        new Directory(
                            new Name(\str_repeat('a', 255)),
                            Set::of(
                                FileInterface::class,
                                new File(
                                    new Name(\str_repeat('a', 255)),
                                    Stream::ofContent('foo')
                                )
                            )
                        )
                    )
                )
            )
        ));
    }

    public function testPersistedNameCanStartWithAnyAsciiCharacter()
    {
        $this
            ->forAll(
                new DataSet\Either(
                    DataSet\Integers::between(1, 46),
                    DataSet\Integers::between(48, 127),
                ),
                DataSet\Strings::any(),
            )
            ->then(function($ord, $content) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                $filesystem = new Filesystem(Path::of($path));

                $this->assertNull($filesystem->add(new Directory(
                    new Name(\chr($ord).'a'),
                    Set::of(
                        FileInterface::class,
                        new File(
                            new Name('a'),
                            Stream::ofContent($content),
                        ),
                    ),
                )));

                (new FS)->remove($path);
            });
    }

    public function testPersistedNameCanContainWithAnyAsciiCharacter()
    {
        $this
            ->forAll(
                new DataSet\Either(
                    DataSet\Integers::between(1, 46),
                    DataSet\Integers::between(48, 127),
                ),
                DataSet\Strings::any(),
            )
            ->then(function($ord, $content) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                $filesystem = new Filesystem(Path::of($path));

                $this->assertNull($filesystem->add(new Directory(
                    new Name('a'.\chr($ord).'a'),
                    Set::of(
                        FileInterface::class,
                        new File(
                            new Name('a'),
                            Stream::ofContent($content),
                        ),
                    ),
                )));

                (new FS)->remove($path);
            });
    }

    public function testPersistedNameCanContainOnlyOneAsciiCharacter()
    {
        $this
            ->forAll(
                new DataSet\Either(
                    DataSet\Integers::between(1, 8),
                    DataSet\Integers::between(14, 31),
                    DataSet\Integers::between(33, 45),
                    DataSet\Integers::between(48, 127),
                ),
                DataSet\Strings::any(),
            )
            ->then(function($ord, $content) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                $filesystem = new Filesystem(Path::of($path));

                $this->assertNull($filesystem->add(new Directory(
                    new Name(\chr($ord)),
                    Set::of(
                        FileInterface::class,
                        new File(
                            new Name('a'),
                            Stream::ofContent($content),
                        ),
                    ),
                )));

                (new FS)->remove($path);
            });
    }

    public function testThrowsWhenTryingToPersistClosedFileStream()
    {
        $this
            ->forAll(FFile::any())
            ->then(function($file) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                $filesystem = new Filesystem(Path::of($path));

                $file->content()->close();
                $this->expectException(CannotPersistClosedStream::class);

                $filesystem->add($file);
            });
    }

    public function testThrowsWhenTryingToGetLink()
    {
        $path = \sys_get_temp_dir().'/innmind/filesystem/';
        (new FS)->remove($path);
        (new FS)->dumpFile($path.'foo', 'bar');
        \symlink($path.'foo', $path.'bar');

        $filesystem = new Filesystem(Path::of($path));

        $this->expectException(LinksAreNotSupported::class);
        $this->expectExceptionMessage($path.'bar');

        $filesystem->get(new Name('bar'));
    }

    public function testThrowsWhenListContainsALink()
    {
        $path = \sys_get_temp_dir().'/innmind/filesystem/';
        (new FS)->remove($path);
        (new FS)->dumpFile($path.'foo', 'bar');
        \symlink($path.'foo', $path.'bar');

        $filesystem = new Filesystem(Path::of($path));

        $this->expectException(LinksAreNotSupported::class);
        $this->expectExceptionMessage($path.'bar');

        unwrap($filesystem->all());
    }

    public function testDotFilesAreListed()
    {
        $this
            ->forAll(FName::strings())
            ->then(function($name) {
                $name = ".$name";
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);
                (new FS)->dumpFile($path.$name, 'bar');

                $filesystem = new Filesystem(Path::of($path));

                $all = unwrap($filesystem->all());
                $this->assertCount(1, $all);
                $this->assertSame($name, $all[0]->name()->toString());
            });
    }

    public function properties(): iterable
    {
        foreach (PAdapter::list() as $property) {
            yield [$property];
        }
    }
}
