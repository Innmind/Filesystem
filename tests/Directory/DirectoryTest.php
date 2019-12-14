<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Directory;

use Innmind\Filesystem\{
    Directory\Directory,
    Directory as DirectoryInterface,
    File,
    Event\FileWasAdded,
    Event\FileWasRemoved,
    Exception\FileNotFound,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class DirectoryTest extends TestCase
{
    public function testInterface()
    {
        $d = new Directory('foo');

        $this->assertInstanceOf(DirectoryInterface::class, $d);
        $this->assertSame('foo', $d->name()->toString());
        $this->assertSame('', $d->content()->toString());
        $this->assertSame('text/directory', $d->mediaType()->toString());
        $this->assertSame($d->mediaType(), $d->mediaType());
    }

    public function testAdd()
    {
        $d = new Directory('foo');
        $d->content(); //force generation of files list, to be sure it's not cloned

        $d2 = $d->add(
            $file = new File\File('foo', Stream::ofContent('bar'))
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
        $d = (new Directory('foo'))
            ->add($f = new File\File('bar', Stream::ofContent('baz')));

        $this->assertSame($f, $d->get('bar'));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('bar');

        (new Directory('foo'))->get('bar');
    }

    public function testContains()
    {
        $d = (new Directory('foo'))
            ->add(new File\File('bar', Stream::ofContent('baz')));

        $this->assertFalse($d->contains('baz'));
        $this->assertTrue($d->contains('bar'));
    }

    public function testRemove()
    {
        $d = (new Directory('foo'))
            ->add(new File\File('bar', Stream::ofContent('baz')));
        $d->content(); //force generation of files list, to be sure it's not cloned

        $d2 = $d->remove('bar');

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
        $this->assertSame('bar', $d2->modifications()->get(1)->file());
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $dir = new Directory('foo');

        $this->assertSame($dir, $dir->remove('bar'));
    }

    public function testGenerator()
    {
        $d = new Directory(
            'foo',
            Set::defer(File::class, (function () {
                yield new File\File('foo', Stream::ofContent('foo'));
                yield new File\File('bar', Stream::ofContent('bar'));
                yield new File\File('foobar', Stream::ofContent('foobar'));
                yield new Directory('sub');
            })()),
        );

        $this->assertSame(
            'foo' . "\n" . 'bar' . "\n" . 'foobar' . "\n" . 'sub',
            $d->content()->toString()
        );
    }

    public function testReplaceAt()
    {
        $d = (new Directory('foobar'))
            ->add(
                (new Directory('foo'))
                    ->add(
                        (new Directory('bar'))
                            ->add(
                                (new Directory('baz'))
                                    ->add(new File\File('baz.md', Stream::ofContent('baz')))
                            )
                    )
            );

        $d2 = $d->replaceAt(
            'foo/bar/baz',
            new File\File('baz.md', Stream::ofContent('updated'))
        );
        $this->assertInstanceOf(DirectoryInterface::class, $d2);
        $this->assertNotSame($d, $d2);
        $this->assertSame(
            'baz',
            $d->get('foo')->get('bar')->get('baz')->get('baz.md')->content()->toString()
        );
        $this->assertSame(
            'updated',
            $d2->get('foo')->get('bar')->get('baz')->get('baz.md')->content()->toString()
        );
    }

    public function testForeach()
    {
        $directory = new Directory(
            'foo',
            Set::defer(File::class, (function () {
                yield new File\File('foo', Stream::ofContent('foo'));
                yield new File\File('bar', Stream::ofContent('bar'));
                yield new File\File('foobar', Stream::ofContent('foobar'));
                yield new Directory('sub');
            })()),
        );

        $called = 0;
        $this->assertNull($directory->foreach(function() use (&$called) {
            ++$called;
        }));
        $this->assertSame(4, $called);
    }

    public function testReduce()
    {
        $directory = new Directory(
            'foo',
            Set::defer(File::class, (function () {
                yield new File\File('foo', Stream::ofContent('foo'));
                yield new File\File('bar', Stream::ofContent('bar'));
                yield new File\File('foobar', Stream::ofContent('foobar'));
                yield new Directory('sub');
            })()),
        );

        $reduced = $directory->reduce(
            '',
            fn($carry, $file) => $carry.$file->name()->toString(),
        );

        $this->assertSame('foobarfoobarsub', $reduced);
    }
}
