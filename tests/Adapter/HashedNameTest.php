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
    Exception\LogicException,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
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
            new HashedName($this->createMock(Adapter::class))
        );
    }

    public function testThrowWhenAddingADirectory()
    {
        $filesystem = new HashedName($this->createMock(Adapter::class));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A directory can\'t be hashed');

        $filesystem->add($this->createMock(Directory::class));
    }

    public function testFileLifecycle()
    {
        $filesystem = new HashedName(
            $inner = new Filesystem(Path::of('/tmp/hashed/'))
        );

        $file = new File\File(new Name('foo'), Stream::ofContent('content'));

        $this->assertFalse($filesystem->contains(new Name('foo')));
        $this->assertNull($filesystem->add($file));
        $this->assertTrue($filesystem->contains(new Name('foo')));
        $this->assertSame(
            'content',
            (string) $inner
                ->get(new Name('0b'))
                ->flatMap(static fn($directory) => $directory->get(new Name('ee')))
                ->flatMap(static fn($directory) => $directory->get(new Name('c7b5ea3f0fdbc95d0dd47f3c5bc275da8a33')))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );
        $this->assertSame(
            'content',
            $filesystem
                ->get(new Name('foo'))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        $file = new File\File(new Name('foo'), Stream::ofContent('content bis'));

        $this->assertNull($filesystem->add($file));
        $this->assertSame(
            'content bis',
            (string) $inner
                ->get(new Name('0b'))
                ->flatMap(static fn($directory) => $directory->get(new Name('ee')))
                ->flatMap(static fn($directory) => $directory->get(new Name('c7b5ea3f0fdbc95d0dd47f3c5bc275da8a33')))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        $this->assertNull($filesystem->remove(new Name('foo')));
        $this->assertFalse($filesystem->contains(new Name('foo')));
    }

    public function testReturnNothingWhenGettingUnknownFile()
    {
        $filesystem = new HashedName(
            new Filesystem(Path::of('/tmp/hashed/'))
        );

        $this->assertNull($filesystem->get(new Name('foo'))->match(
            static fn($file) => $file,
            static fn() => null,
        ));
    }

    public function testAll()
    {
        $filesystem = new HashedName(
            new Filesystem(Path::of('/tmp/hashed/'))
        );

        $filesystem->add(new File\File(new Name('foo'), Stream::ofContent('content')));
        $filesystem->add(new File\File(new Name('bar'), Stream::ofContent('content')));

        $all = $filesystem->all();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(File::class, $all->type());
        $this->assertCount(2, $all);
        //as described in the method comment we return the inner structure instead of the files
        $files = unwrap($all);
        $this->assertInstanceOf(Directory::class, $files[0]);
        $this->assertInstanceOf(Directory::class, $files[1]);
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $filesystem = new HashedName(
            new Filesystem(Path::of('/tmp/hashed/'))
        );

        $this->assertNull($filesystem->remove(new Name('foo')));
    }
}
