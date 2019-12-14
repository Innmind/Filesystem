<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\FilesystemAdapter,
    Adapter,
    File\File,
    Name\Name,
    File as FileInterface,
    Directory\Directory,
    MediaType\NullMediaType,
    Exception\FileNotFound,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class FilesystemAdapterTest extends TestCase
{
    public function testInterface()
    {
        $adapter = new FilesystemAdapter('/tmp');

        $this->assertInstanceOf(Adapter::class, $adapter);
        $this->assertFalse($adapter->contains(new Name('foo')));
        $this->assertNull($adapter->add(new Directory('foo')));
        $this->assertTrue($adapter->contains(new Name('foo')));
        $this->assertNull($adapter->remove(new Name('foo')));
        $this->assertFalse($adapter->contains(new Name('foo')));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        (new FilesystemAdapter('/tmp'))->get(new Name('foo'));
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertNull((new FilesystemAdapter('/tmp'))->remove(new Name('foo')));
    }

    public function testCreateNestedStructure()
    {
        $adapter = new FilesystemAdapter('/tmp');

        $directory = (new Directory('foo'))
            ->add(new File('foo.md', Stream::ofContent('# Foo')))
            ->add(
                (new Directory('bar'))
                    ->add(new File('bar.md', Stream::ofContent('# Bar')))
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

        $adapter = new FilesystemAdapter('/tmp');
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
        $a = new FilesystemAdapter('/tmp');

        $d = new Directory('foo');
        $d = $d->add(new File('bar', Stream::ofContent('some content')));
        $a->add($d);
        $d = $d->remove(new Name('bar'));
        $a->add($d);
        $this->assertSame(2, $d->modifications()->count());
        $a = new FilesystemAdapter('/tmp');
        $this->assertFalse($a->get(new Name('foo'))->contains(new Name('bar')));
        $a->remove(new Name('foo'));
    }

    public function testDoesntFailWhenAddindSameDirectoryTwiceThatContainsARemovedFile()
    {
        $a = new FilesystemAdapter('/tmp');

        $d = new Directory('foo');
        $d = $d->add(new File('bar', Stream::ofContent('some content')));
        $a->add($d);
        $d = $d->remove(new Name('bar'));
        $a->add($d);
        $a->add($d);
        $this->assertSame(2, $d->modifications()->count());
        $a = new FilesystemAdapter('/tmp');
        $this->assertFalse($a->get(new Name('foo'))->contains(new Name('bar')));
        $a->remove(new Name('foo'));
    }

    public function testLoadWithMediaType()
    {
        $a = new FilesystemAdapter('/tmp');
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
        $adapter = new FilesystemAdapter('/tmp/test');
        $adapter->add(new File(
            'foo',
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

    public function testFallbackToNullMediaTypeWhenDetectedWhenIsNotAnOfficialOne()
    {
        if (\PHP_MAJOR_VERSION === 7 && \PHP_MINOR_VERSION === 4) {
            // php 7.4 correctly reads the media type of the file
            return;
        }

        file_put_contents('/tmp/test/bar', '');
        $adapter = new FilesystemAdapter('/tmp/test');

        $this->assertInstanceOf(
            NullMediaType::class,
            $adapter->get(new Name('bar'))->mediaType()
        );
    }

    public function testDotPseudoFilesAreNotListedInDirectory()
    {
        @mkdir('/tmp/test');
        $adapter = new FilesystemAdapter('/tmp');

        $this->assertFalse($adapter->get(new Name('test'))->contains(new Name('.')));
        $this->assertFalse($adapter->get(new Name('test'))->contains(new Name('..')));
        $this->assertFalse($adapter->contains(new Name('.')));
        $this->assertFalse($adapter->contains(new Name('..')));

        $this->expectException(FileNotFound::class);

        $adapter->get(new Name('..'));
    }
}
