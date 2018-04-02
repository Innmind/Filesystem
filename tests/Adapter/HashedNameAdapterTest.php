<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\HashedNameAdapter,
    Adapter\FilesystemAdapter,
    Adapter,
    Directory,
    File,
    Stream\StringStream,
    Exception\LogicException,
    Exception\FileNotFound
};
use Innmind\Immutable\MapInterface;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class HashedNameAdapterTest extends TestCase
{
    public function setUp()
    {
        (new Filesystem)->remove('/tmp/hashed');
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Adapter::class,
            new HashedNameAdapter($this->createMock(Adapter::class))
        );
    }

    public function testThrowWhenAddingADirectory()
    {
        $filesystem = new HashedNameAdapter($this->createMock(Adapter::class));

        $this->expectException(LogicException::class);

        $filesystem->add($this->createMock(Directory::class));
    }

    public function testFileLifecycle()
    {
        $filesystem = new HashedNameAdapter(
            $inner = new FilesystemAdapter('/tmp/hashed')
        );

        $file = new File\File('foo', new StringStream('content'));

        $this->assertFalse($filesystem->has('foo'));
        $this->assertSame($filesystem, $filesystem->add($file));
        $this->assertTrue($filesystem->has('foo'));
        $this->assertSame(
            'content',
            (string) $inner
                ->get('0b')
                ->get('ee')
                ->get('c7b5ea3f0fdbc95d0dd47f3c5bc275da8a33')
                ->content()
        );

        $file = new File\File('foo', new StringStream('content bis'));

        $this->assertSame($filesystem, $filesystem->add($file));
        $this->assertSame(
            'content bis',
            (string) $inner
                ->get('0b')
                ->get('ee')
                ->get('c7b5ea3f0fdbc95d0dd47f3c5bc275da8a33')
                ->content()
        );

        $this->assertSame($filesystem, $filesystem->remove('foo'));
        $this->assertFalse($filesystem->has('foo'));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $filesystem = new HashedNameAdapter(
            new FilesystemAdapter('/tmp/hashed')
        );

        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        $filesystem->get('foo');
    }

    public function testAll()
    {
        $filesystem = new HashedNameAdapter(
            new FilesystemAdapter('/tmp/hashed')
        );

        $filesystem->add(new File\File('foo', new StringStream('content')));
        $filesystem->add(new File\File('bar', new StringStream('content')));

        $all = $filesystem->all();

        $this->assertInstanceOf(MapInterface::class, $all);
        $this->assertSame('string', (string) $all->keyType());
        $this->assertSame(File::class, (string) $all->valueType());
        $this->assertCount(2, $all);
        //as described in the method comment we return the inner structure instead of the files
        $this->assertInstanceOf(Directory::class, $all->current());
        $all->next();
        $this->assertInstanceOf(Directory::class, $all->current());
    }

    public function testThrowWhenRemovingUnknownFile()
    {
        $filesystem = new HashedNameAdapter(
            new FilesystemAdapter('/tmp/hashed')
        );

        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        $filesystem->remove('foo');
    }
}
