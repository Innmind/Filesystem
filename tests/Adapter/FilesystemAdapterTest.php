<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\FilesystemAdapter,
    Adapter,
    File\File,
    Name,
    File as FileInterface,
    Directory\Directory,
    MediaType\NullMediaType,
    Exception\FileNotFound,
    Exception\PathDoesntRepresentADirectory,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class FilesystemAdapterTest extends TestCase
{
    public function setUp(): void
    {
        $fs = new Filesystem;
        $fs->remove('/tmp/test');
        $fs->remove('/tmp/foo');
    }

    public function testInterface()
    {
        $adapter = new FilesystemAdapter(Path::of('/tmp/'));

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

        new FilesystemAdapter(Path::of('path/to/somewhere'));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        (new FilesystemAdapter(Path::of('/tmp/')))->get(new Name('foo'));
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertNull((new FilesystemAdapter(Path::of('/tmp/')))->remove(new Name('foo')));
    }

    public function testCreateNestedStructure()
    {
        $adapter = new FilesystemAdapter(Path::of('/tmp/'));

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

        $adapter = new FilesystemAdapter(Path::of('/tmp/'));
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
        $a = new FilesystemAdapter(Path::of('/tmp/'));

        $d = new Directory(new Name('foo'));
        $d = $d->add(new File(new Name('bar'), Stream::ofContent('some content')));
        $a->add($d);
        $d = $d->remove(new Name('bar'));
        $a->add($d);
        $this->assertSame(2, $d->modifications()->count());
        $a = new FilesystemAdapter(Path::of('/tmp/'));
        $this->assertFalse($a->get(new Name('foo'))->contains(new Name('bar')));
        $a->remove(new Name('foo'));
    }

    public function testDoesntFailWhenAddindSameDirectoryTwiceThatContainsARemovedFile()
    {
        $a = new FilesystemAdapter(Path::of('/tmp/'));

        $d = new Directory(new Name('foo'));
        $d = $d->add(new File(new Name('bar'), Stream::ofContent('some content')));
        $a->add($d);
        $d = $d->remove(new Name('bar'));
        $a->add($d);
        $a->add($d);
        $this->assertSame(2, $d->modifications()->count());
        $a = new FilesystemAdapter(Path::of('/tmp/'));
        $this->assertFalse($a->get(new Name('foo'))->contains(new Name('bar')));
        $a->remove(new Name('foo'));
    }

    public function testLoadWithMediaType()
    {
        $a = new FilesystemAdapter(Path::of('/tmp/'));
        file_put_contents(
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
        $adapter = new FilesystemAdapter(Path::of('/tmp/test/'));
        $adapter->add(new File(
            new Name('foo'),
            Stream::ofContent('foo')
        ));
        file_put_contents('/tmp/test/bar', 'bar');
        mkdir('/tmp/test/baz');
        file_put_contents('/tmp/test/baz/foobar', 'baz');

        $all = $adapter->all();
        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(FileInterface::class, $all->type());
        $this->assertCount(3, $all);
        $all = $all->toMapOf(
            'string',
            FileInterface::class,
            fn($file) => yield $file->name()->toString() => $file,
        );
        $this->assertTrue($all->contains('foo'));
        $this->assertTrue($all->contains('bar'));
        $this->assertTrue($all->contains('baz'));
        $this->assertSame('foo', $adapter->get(new Name('foo'))->content()->toString());
        $this->assertSame('bar', $adapter->get(new Name('bar'))->content()->toString());
        $this->assertInstanceOf(Directory::class, $adapter->get(new Name('baz')));
        $adapter->remove(new Name('foo'));
        $adapter->remove(new Name('bar'));
        $adapter->remove(new Name('baz'));
    }

    public function testDotPseudoFilesAreNotListedInDirectory()
    {
        @mkdir('/tmp/test');
        $adapter = new FilesystemAdapter(Path::of('/tmp/'));

        $this->assertFalse($adapter->get(new Name('test'))->contains(new Name('.')));
        $this->assertFalse($adapter->get(new Name('test'))->contains(new Name('..')));
        $this->assertFalse($adapter->contains(new Name('.')));
        $this->assertFalse($adapter->contains(new Name('..')));
        $this->assertFalse(
            $adapter
                ->all()
                ->reduce(
                    false,
                    fn($found, $file) => $found || $file->name()->equals(new Name('.')) || $file->name()->equals(new Name('..')),
                ),
        );

        $this->expectException(FileNotFound::class);

        $adapter->get(new Name('..'));
    }
}
