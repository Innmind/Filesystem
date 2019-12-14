<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\FilesystemAdapter,
    Adapter,
    File\File,
    File as FileInterface,
    Directory\Directory,
    Stream\StringStream,
    MediaType\NullMediaType,
    Exception\FileNotFound
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class FilesystemAdapterTest extends TestCase
{
    public function testInterface()
    {
        $adapter = new FilesystemAdapter('/tmp');

        $this->assertInstanceOf(Adapter::class, $adapter);
        $this->assertFalse($adapter->has('foo'));
        $this->assertNull($adapter->add(new Directory('foo')));
        $this->assertTrue($adapter->has('foo'));
        $this->assertNull($adapter->remove('foo'));
        $this->assertFalse($adapter->has('foo'));
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFound
     * @expectedExceptionMessage foo
     */
    public function testThrowWhenGettingUnknownFile()
    {
        (new FilesystemAdapter('/tmp'))->get('foo');
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFound
     * @expectedExceptionMessage foo
     */
    public function testThrowWhenRemovingUnknownFile()
    {
        (new FilesystemAdapter('/tmp'))->remove('foo');
    }

    public function testCreateNestedStructure()
    {
        $adapter = new FilesystemAdapter('/tmp');

        $directory = (new Directory('foo'))
            ->add(new File('foo.md', new StringStream('# Foo')))
            ->add(
                (new Directory('bar'))
                    ->add(new File('bar.md', new StringStream('# Bar')))
            );
        $adapter->add($directory);
        $this->assertSame(
            '# Foo',
            $adapter->get('foo')->get('foo.md')->content()->toString()
        );
        $this->assertSame(
            '# Bar',
            $adapter->get('foo')->get('bar')->get('bar.md')->content()->toString()
        );

        $adapter = new FilesystemAdapter('/tmp');
        //check it's really persisted (otherwise it will throw)
        $adapter->get('foo');
        $this->assertSame(
            '# Foo',
            $adapter->get('foo')->get('foo.md')->content()->toString()
        );
        $adapter->get('foo')->get('bar');
        $this->assertSame(
            '# Bar',
            $adapter->get('foo')->get('bar')->get('bar.md')->content()->toString()
        );

        $adapter->remove('foo');
    }

    public function testRemoveFileWhenRemovedFromFolder()
    {
        $a = new FilesystemAdapter('/tmp');

        $d = new Directory('foo');
        $d = $d->add(new File('bar', new StringStream('some content')));
        $a->add($d);
        $d = $d->remove('bar');
        $a->add($d);
        $this->assertSame(2, $d->modifications()->count());
        $a = new FilesystemAdapter('/tmp');
        $this->assertFalse($a->get('foo')->has('bar'));
        $a->remove('foo');
    }

    public function testDoesntFailWhenAddindSameDirectoryTwiceThatContainsARemovedFile()
    {
        $a = new FilesystemAdapter('/tmp');

        $d = new Directory('foo');
        $d = $d->add(new File('bar', new StringStream('some content')));
        $a->add($d);
        $d = $d->remove('bar');
        $a->add($d);
        $a->add($d);
        $this->assertSame(2, $d->modifications()->count());
        $a = new FilesystemAdapter('/tmp');
        $this->assertFalse($a->get('foo')->has('bar'));
        $a->remove('foo');
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
            $a->get('some_content.html')->mediaType()->toString()
        );
        $a->remove('some_content.html');
    }

    public function testAll()
    {
        $adapter = new FilesystemAdapter('/tmp/test');
        $adapter->add(new File(
            'foo',
            new StringStream('foo')
        ));
        file_put_contents('/tmp/test/bar', 'bar');
        mkdir('/tmp/test/baz');
        file_put_contents('/tmp/test/baz/foobar', 'baz');

        $all = $adapter->all();
        $this->assertInstanceOf(Map::class, $all);
        $this->assertSame('string', (string) $all->keyType());
        $this->assertSame(FileInterface::class, (string) $all->valueType());
        $this->assertCount(3, $all);
        $this->assertTrue($all->contains('foo'));
        $this->assertTrue($all->contains('bar'));
        $this->assertTrue($all->contains('baz'));
        $this->assertSame('foo', $adapter->get('foo')->content()->toString());
        $this->assertSame('bar', $adapter->get('bar')->content()->toString());
        $this->assertInstanceOf(Directory::class, $adapter->get('baz'));
        $adapter->remove('foo');
        $adapter->remove('bar');
        $adapter->remove('baz');
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
            $adapter->get('bar')->mediaType()
        );
    }

    public function testDotPseudoFilesAreNotListedInDirectory()
    {
        @mkdir('/tmp/test');
        $adapter = new FilesystemAdapter('/tmp');

        $this->assertFalse($adapter->get('test')->has('.'));
        $this->assertFalse($adapter->get('test')->has('..'));
        $this->assertFalse($adapter->has('.'));
        $this->assertFalse($adapter->has('..'));

        $this->expectException(FileNotFound::class);

        $adapter->get('..');
    }
}
