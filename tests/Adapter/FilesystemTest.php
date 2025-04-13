<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\Filesystem,
    Adapter,
    File,
    File\Content,
    Name,
    Directory as DirectoryInterface,
    Directory,
    Exception\PathDoesntRepresentADirectory,
    Exception\PathTooLong,
    Exception\LinksAreNotSupported,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
    SideEffect,
};
use Symfony\Component\Filesystem\Filesystem as FS;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use Fixtures\Innmind\Filesystem\Name as FName;

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
        $adapter = Filesystem::mount(Path::of('/tmp/'));

        $this->assertInstanceOf(Adapter::class, $adapter);
        $this->assertFalse($adapter->contains(Name::of('foo')));
        $this->assertInstanceOf(
            SideEffect::class,
            $adapter
                ->add(Directory::of(Name::of('foo')))
                ->unwrap(),
        );
        $this->assertTrue($adapter->contains(Name::of('foo')));
        $this->assertInstanceOf(
            SideEffect::class,
            $adapter
                ->remove(Name::of('foo'))
                ->unwrap(),
        );
        $this->assertFalse($adapter->contains(Name::of('foo')));
    }

    public function testThrowWhenPathToMountIsNotADirectory()
    {
        $this->expectException(PathDoesntRepresentADirectory::class);
        $this->expectExceptionMessage('path/to/somewhere');

        Filesystem::mount(Path::of('path/to/somewhere'));
    }

    public function testReturnNothingWhenGettingUnknownFile()
    {
        $this->assertNull(
            Filesystem::mount(Path::of('/tmp/'))
                ->get(Name::of('foo'))
                ->match(
                    static fn($file) => $file,
                    static fn() => null,
                ),
        );
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertInstanceOf(
            SideEffect::class,
            Filesystem::mount(Path::of('/tmp/'))
                ->remove(Name::of('foo'))
                ->unwrap(),
        );
    }

    public function testCreateNestedStructure()
    {
        $adapter = Filesystem::mount(Path::of('/tmp/'));

        $directory = Directory::of(Name::of('foo'))
            ->add(File::of(Name::of('foo.md'), Content::ofString('# Foo')))
            ->add(
                Directory::of(Name::of('bar'))
                    ->add(File::of(Name::of('bar.md'), Content::ofString('# Bar'))),
            );
        $adapter->add($directory)->unwrap();
        $this->assertSame(
            '# Foo',
            $adapter
                ->get(Name::of('foo'))
                ->flatMap(static fn($dir) => $dir->get(Name::of('foo.md')))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );
        $this->assertSame(
            '# Bar',
            $adapter
                ->get(Name::of('foo'))
                ->flatMap(static fn($dir) => $dir->get(Name::of('bar')))
                ->flatMap(static fn($dir) => $dir->get(Name::of('bar.md')))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        $adapter = Filesystem::mount(Path::of('/tmp/'));
        $this->assertTrue($adapter->contains(Name::of('foo')));
        $this->assertSame(
            '# Foo',
            $adapter
                ->get(Name::of('foo'))
                ->flatMap(static fn($dir) => $dir->get(Name::of('foo.md')))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );
        $this->assertTrue(
            $adapter->get(Name::of('foo'))->match(
                static fn($dir) => $dir->contains(Name::of('bar')),
                static fn() => false,
            ),
        );
        $this->assertSame(
            '# Bar',
            $adapter
                ->get(Name::of('foo'))
                ->flatMap(static fn($dir) => $dir->get(Name::of('bar')))
                ->flatMap(static fn($dir) => $dir->get(Name::of('bar.md')))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        $adapter
            ->remove(Name::of('foo'))
            ->unwrap();
    }

    public function testRemoveFileWhenRemovedFromFolder()
    {
        $a = Filesystem::mount(Path::of('/tmp/'));

        $d = Directory::of(Name::of('foo'));
        $d = $d->add(File::of(Name::of('bar'), Content::ofString('some content')));
        $a->add($d)->unwrap();
        $d = $d->remove(Name::of('bar'));
        $a->add($d)->unwrap();
        $this->assertSame(1, $d->removed()->count());
        $a = Filesystem::mount(Path::of('/tmp/'));
        $this->assertFalse(
            $a->get(Name::of('foo'))->match(
                static fn($dir) => $dir->contains(Name::of('bar')),
                static fn() => true,
            ),
        );
        $a
            ->remove(Name::of('foo'))
            ->unwrap();
    }

    public function testDoesntFailWhenAddindSameDirectoryTwiceThatContainsARemovedFile()
    {
        $a = Filesystem::mount(Path::of('/tmp/'));

        $d = Directory::of(Name::of('foo'));
        $d = $d->add(File::of(Name::of('bar'), Content::ofString('some content')));
        $a->add($d)->unwrap();
        $d = $d->remove(Name::of('bar'));
        $a->add($d)->unwrap();
        $a->add($d)->unwrap();
        $this->assertSame(1, $d->removed()->count());
        $a = Filesystem::mount(Path::of('/tmp/'));
        $this->assertFalse(
            $a->get(Name::of('foo'))->match(
                static fn($dir) => $dir->contains(Name::of('bar')),
                static fn() => true,
            ),
        );
        $a
            ->remove(Name::of('foo'))
            ->unwrap();
    }

    public function testLoadWithMediaType()
    {
        $a = Filesystem::mount(Path::of('/tmp/'));
        \file_put_contents(
            '/tmp/some_content.html',
            '<!DOCTYPE html><html><body><answer value="42"/></body></html>',
        );

        $this->assertSame(
            'text/html',
            $a
                ->get(Name::of('some_content.html'))
                ->map(static fn($file) => $file->mediaType())
                ->match(
                    static fn($mediaType) => $mediaType->toString(),
                    static fn() => null,
                ),
        );
        $a
            ->remove(Name::of('some_content.html'))
            ->unwrap();
    }

    public function testRoot()
    {
        $adapter = Filesystem::mount(Path::of('/tmp/test/'));
        $adapter
            ->add(File::of(
                Name::of('foo'),
                Content::ofString('foo'),
            ))
            ->unwrap();
        \file_put_contents('/tmp/test/bar', 'bar');
        \mkdir('/tmp/test/baz');
        \file_put_contents('/tmp/test/baz/foobar', 'baz');

        $all = $adapter->root()->all();
        $this->assertCount(3, $all);
        $all = Map::of(
            ...$all
                ->map(static fn($file) => [$file->name()->toString(), $file])
                ->toList(),
        );
        $this->assertTrue($all->contains('foo'));
        $this->assertTrue($all->contains('bar'));
        $this->assertTrue($all->contains('baz'));
        $this->assertSame(
            'foo',
            $adapter
                ->get(Name::of('foo'))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );
        $this->assertSame(
            'bar',
            $adapter
                ->get(Name::of('bar'))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );
        $this->assertInstanceOf(
            DirectoryInterface::class,
            $adapter->get(Name::of('baz'))->match(
                static fn($dir) => $dir,
                static fn() => null,
            ),
        );
        $adapter
            ->remove(Name::of('foo'))
            ->unwrap();
        $adapter
            ->remove(Name::of('bar'))
            ->unwrap();
        $adapter
            ->remove(Name::of('baz'))
            ->unwrap();
    }

    public function testAddingTheSameFileTwiceDoesNothing()
    {
        $adapter = Filesystem::mount(Path::of('/tmp/'));
        $file = File::of(
            Name::of('foo'),
            Content::ofString('foo'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $adapter
                ->add($file)
                ->unwrap(),
        );
        $this->assertInstanceOf(
            SideEffect::class,
            $adapter
                ->add($file)
                ->unwrap(),
        );
    }

    public function testPathTooLongThrowAnException()
    {
        if (\PHP_OS !== 'Darwin') {
            return;
        }

        $path = \sys_get_temp_dir().'/innmind/filesystem/';
        (new FS)->remove($path);

        $filesystem = Filesystem::mount(Path::of($path));

        $this->expectException(PathTooLong::class);

        $filesystem->add(Directory::of(
            Name::of(\str_repeat('a', 255)),
            Sequence::of(
                Directory::of(
                    Name::of(\str_repeat('a', 255)),
                    Sequence::of(
                        Directory::of(
                            Name::of(\str_repeat('a', 255)),
                            Sequence::of(
                                File::of(
                                    Name::of(\str_repeat('a', 255)),
                                    Content::none(),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ))->unwrap();
    }

    public function testPersistedNameCanStartWithAnyAsciiCharacter()
    {
        $this
            ->forAll(
                Set::either(
                    Set::integers()->between(1, 46),
                    Set::integers()->between(48, 127),
                ),
                Set::strings(),
            )
            ->then(function($ord, $content) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                $filesystem = Filesystem::mount(Path::of($path));

                $this->assertInstanceOf(
                    SideEffect::class,
                    $filesystem->add(Directory::of(
                        Name::of(\chr($ord).'a'),
                        Sequence::of(
                            File::of(
                                Name::of('a'),
                                Content::ofString($content),
                            ),
                        ),
                    ))->unwrap(),
                );

                (new FS)->remove($path);
            });
    }

    public function testPersistedNameCanContainWithAnyAsciiCharacter()
    {
        $this
            ->forAll(
                Set::either(
                    Set::integers()->between(1, 46),
                    Set::integers()->between(48, 127),
                ),
                Set::strings(),
            )
            ->then(function($ord, $content) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                $filesystem = Filesystem::mount(Path::of($path));

                $this->assertInstanceOf(
                    SideEffect::class,
                    $filesystem->add(Directory::of(
                        Name::of('a'.\chr($ord).'a'),
                        Sequence::of(
                            File::of(
                                Name::of('a'),
                                Content::ofString($content),
                            ),
                        ),
                    ))->unwrap(),
                );

                (new FS)->remove($path);
            });
    }

    public function testPersistedNameCanContainOnlyOneAsciiCharacter()
    {
        $this
            ->forAll(
                Set::either(
                    Set::integers()->between(1, 8),
                    Set::integers()->between(14, 31),
                    Set::integers()->between(33, 45),
                    Set::integers()->between(48, 127),
                ),
                Set::strings(),
            )
            ->then(function($ord, $content) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                $filesystem = Filesystem::mount(Path::of($path));

                $this->assertInstanceOf(
                    SideEffect::class,
                    $filesystem->add(Directory::of(
                        Name::of(\chr($ord)),
                        Sequence::of(
                            File::of(
                                Name::of('a'),
                                Content::ofString($content),
                            ),
                        ),
                    ))->unwrap(),
                );

                (new FS)->remove($path);
            });
    }

    public function testThrowsWhenTryingToGetLink()
    {
        $path = \sys_get_temp_dir().'/innmind/filesystem/';
        (new FS)->remove($path);
        (new FS)->dumpFile($path.'foo', 'bar');
        \symlink($path.'foo', $path.'bar');

        $filesystem = Filesystem::mount(Path::of($path));

        $this->expectException(LinksAreNotSupported::class);
        $this->expectExceptionMessage($path.'bar');

        $filesystem->get(Name::of('bar'));
    }

    public function testThrowsWhenListContainsALink()
    {
        $path = \sys_get_temp_dir().'/innmind/filesystem/';
        (new FS)->remove($path);
        (new FS)->dumpFile($path.'foo', 'bar');
        \symlink($path.'foo', $path.'bar');

        $filesystem = Filesystem::mount(Path::of($path));

        $this->expectException(LinksAreNotSupported::class);
        $this->expectExceptionMessage($path.'bar');

        $filesystem->root()->all()->toList();
    }

    public function testDotFilesAreListed()
    {
        $this
            ->forAll(FName::strings()->prefixedBy('.'))
            ->then(function($name) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);
                (new FS)->mkdir($path);
                \file_put_contents($path.$name, 'bar');

                $filesystem = Filesystem::mount(Path::of($path));

                $all = $filesystem->root()->all()->toList();
                $this->assertCount(1, $all);
                $this->assertSame($name, $all[0]->name()->toString());
            });
    }
}
