<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Adapter;

use Innmind\Filesystem\{
    Adapter\FilesystemAdapter,
    AdapterInterface,
    File,
    Directory,
    Stream\StringStream
};

class FilesystemAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $a = new FilesystemAdapter('/tmp');

        $this->assertInstanceOf(AdapterInterface::class, $a);
        $this->assertFalse($a->has('foo'));
        $this->assertSame($a, $a->add($d = new Directory('foo')));
        $this->assertTrue($a->has('foo'));
        $this->assertSame($d, $a->get('foo'));
        $this->assertSame($a, $a->remove('foo'));
        $this->assertFalse($a->has('foo'));
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFoundException
     */
    public function testThrowWhenGettingUnknownFile()
    {
        (new FilesystemAdapter('/tmp'))->get('foo');
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFoundException
     */
    public function testThrowWhenRemovingUnknownFile()
    {
        (new FilesystemAdapter('/tmp'))->remove('foo');
    }

    public function testCreateNestedStructure()
    {
        $a = new FilesystemAdapter('/tmp');

        $d = (new Directory('foo'))
            ->add($f = new File('foo.md', new StringStream('# Foo')))
            ->add(
                $d2 = (new Directory('bar'))
                    ->add($f2 = new File('bar.md', new StringStream('# Bar')))
            );
        $a->add($d);
        $this->assertSame($d, $a->get('foo'));
        $this->assertSame($f, $a->get('foo')->get('foo.md'));
        $this->assertSame($d2, $a->get('foo')->get('bar'));
        $this->assertSame($f2, $a->get('foo')->get('bar')->get('bar.md'));

        $a = new FilesystemAdapter('/tmp');
        //check it's really persisted (otherwise it will throw)
        $a->get('foo');
        $this->assertSame(
            '# Foo',
            (string) $a->get('foo')->get('foo.md')->content()
        );
        $a->get('foo')->get('bar');
        $this->assertSame(
            '# Bar',
            (string) $a->get('foo')->get('bar')->get('bar.md')->content()
        );

        $a->remove('foo');
    }

    public function testRemoveFileWhenRemovedFromFolder()
    {
        $a = new FilesystemAdapter('/tmp');

        $d = new Directory('foo');
        $d = $d->add(new File('bar', new StringStream('some content')));
        $a->add($d);
        $d = $d->remove('bar');
        $a->add($d);
        $this->assertSame(2, $d->recordedEvents()->count());
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
        $this->assertSame(2, $d->recordedEvents()->count());
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
            (string) $a->get('some_content.html')->mediaType()
        );
        $a->remove('some_content.html');
    }
}
