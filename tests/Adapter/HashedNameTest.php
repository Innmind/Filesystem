<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\HashedName,
    Adapter\Filesystem,
    Adapter,
    Directory,
    File,
    Name,
    File\Content\Lines,
    Exception\LogicException,
};
use Innmind\Url\Path;
use Innmind\Immutable\Set;
use Symfony\Component\Filesystem\Filesystem as FS;
use PHPUnit\Framework\TestCase;

class HashedNameTest extends TestCase
{
    public function setUp(): void
    {
        (new FS)->remove('/tmp/hashed/');
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Adapter::class,
            HashedName::of($this->createMock(Adapter::class)),
        );
    }

    public function testThrowWhenAddingADirectory()
    {
        $filesystem = HashedName::of($this->createMock(Adapter::class));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A directory can\'t be hashed');

        $filesystem->add($this->createMock(Directory::class));
    }

    public function testFileLifecycle()
    {
        $filesystem = HashedName::of(
            $inner = Filesystem::mount(Path::of('/tmp/hashed/')),
        );

        $file = new File\File(Name::of('foo'), Lines::ofContent('content'));

        $this->assertFalse($filesystem->contains(Name::of('foo')));
        $this->assertNull($filesystem->add($file));
        $this->assertTrue($filesystem->contains(Name::of('foo')));
        $this->assertSame(
            'content',
            (string) $inner
                ->get(Name::of('0b'))
                ->flatMap(static fn($directory) => $directory->get(Name::of('ee')))
                ->flatMap(static fn($directory) => $directory->get(Name::of('c7b5ea3f0fdbc95d0dd47f3c5bc275da8a33')))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );
        $this->assertSame(
            'content',
            $filesystem
                ->get(Name::of('foo'))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        $file = new File\File(Name::of('foo'), Lines::ofContent('content bis'));

        $this->assertNull($filesystem->add($file));
        $this->assertSame(
            'content bis',
            (string) $inner
                ->get(Name::of('0b'))
                ->flatMap(static fn($directory) => $directory->get(Name::of('ee')))
                ->flatMap(static fn($directory) => $directory->get(Name::of('c7b5ea3f0fdbc95d0dd47f3c5bc275da8a33')))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        $this->assertNull($filesystem->remove(Name::of('foo')));
        $this->assertFalse($filesystem->contains(Name::of('foo')));
    }

    public function testReturnNothingWhenGettingUnknownFile()
    {
        $filesystem = HashedName::of(
            Filesystem::mount(Path::of('/tmp/hashed/')),
        );

        $this->assertNull($filesystem->get(Name::of('foo'))->match(
            static fn($file) => $file,
            static fn() => null,
        ));
    }

    public function testAll()
    {
        $filesystem = HashedName::of(
            Filesystem::mount(Path::of('/tmp/hashed/')),
        );

        $filesystem->add(new File\File(Name::of('foo'), Lines::ofContent('content')));
        $filesystem->add(new File\File(Name::of('bar'), Lines::ofContent('content')));

        $all = $filesystem->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertCount(2, $all);
        //as described in the method comment we return the inner structure instead of the files
        $files = $all->toList();
        $this->assertInstanceOf(Directory::class, $files[0]);
        $this->assertInstanceOf(Directory::class, $files[1]);
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $filesystem = HashedName::of(
            Filesystem::mount(Path::of('/tmp/hashed/')),
        );

        $this->assertNull($filesystem->remove(Name::of('foo')));
    }
}
