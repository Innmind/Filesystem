<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Directory;

use Innmind\Filesystem\{
    Directory\Directory,
    Directory as DirectoryInterface,
    File,
    Stream\StringStream,
    Event\FileWasAdded,
    Event\FileWasRemoved
};
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
            $file = new File\File('foo', new StringStream('bar'))
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
            ->add($f = new File\File('bar', new StringStream('baz')));

        $this->assertSame($f, $d->get('bar'));
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFound
     */
    public function testThrowWhenGettingUnknownFile()
    {
        (new Directory('foo'))->get('bar');
    }

    public function testHas()
    {
        $d = (new Directory('foo'))
            ->add(new File\File('bar', new StringStream('baz')));

        $this->assertFalse($d->has('baz'));
        $this->assertTrue($d->has('bar'));
    }

    public function testRemove()
    {
        $d = (new Directory('foo'))
            ->add(new File\File('bar', new StringStream('baz')));
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

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFound
     */
    public function testThrowWhenRemovingUnknownFile()
    {
        (new Directory('foo'))->remove('bar');
    }

    public function testCount()
    {
        $this->assertSame(0, (new Directory('foo'))->count());
        $this->assertSame(
            1,
            (new Directory('foo'))
                ->add(new File\File('bar', new StringStream('baz')))
                ->count()
        );
    }

    public function testIterator()
    {
        $d = (new Directory('foo'))
            ->add($f1 = new File\File('bar', new StringStream('baz')))
            ->add($f2 = new File\File('baz', new StringStream('baz')))
            ->add($f3 = new File\File('foobar', new StringStream('baz')));

        $this->assertSame($f1, $d->current());
        $this->assertSame($f1->name(), $d->key());
        $this->assertTrue($d->valid());
        $this->assertSame(null, $d->next());
        $this->assertSame($f2, $d->current());
        $this->assertSame($f2->name(), $d->key());
        $d->next();
        $d->next();
        $this->assertFalse($d->valid());
        $this->assertSame(null, $d->rewind());
        $this->assertSame($f1, $d->current());
        $this->assertTrue($d->valid());
    }

    public function testGenerator()
    {
        $d = new Directory(
            'foo',
            (function () {
                yield new File\File('foo', new StringStream('foo'));
                yield new File\File('bar', new StringStream('bar'));
                yield new File\File('foobar', new StringStream('foobar'));
                yield new Directory('sub');
            })()
        );

        $this->assertSame(4, $d->count());
        $this->assertSame('foo', $d->key()->toString());
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
                                    ->add(new File\File('baz.md', new StringStream('baz')))
                            )
                    )
            );

        $d2 = $d->replaceAt(
            'foo/bar/baz',
            new File\File('baz.md', new StringStream('updated'))
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
}
