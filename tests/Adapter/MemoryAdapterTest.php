<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\MemoryAdapter,
    Adapter,
    Directory\Directory,
    File as FileInterface,
    File\File,
    Exception\FileNotFound,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class MemoryAdapterTest extends TestCase
{
    public function testInterface()
    {
        $a = new MemoryAdapter;

        $this->assertInstanceOf(Adapter::class, $a);
        $this->assertFalse($a->contains('foo'));
        $this->assertNull(
            $a->add($d = new Directory('foo'))
        );
        $this->assertTrue($a->contains('foo'));
        $this->assertSame($d, $a->get('foo'));
        $this->assertNull($a->remove('foo'));
        $this->assertFalse($a->contains('foo'));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        (new MemoryAdapter)->get('foo');
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertNull((new MemoryAdapter)->remove('foo'));
    }

    public function testAll()
    {
        $adapter = new MemoryAdapter;
        $adapter->add($foo = new File(
            'foo',
            Stream::ofContent('foo')
        ));
        $adapter->add($bar = new File(
            'bar',
            Stream::ofContent('bar')
        ));

        $all = $adapter->all();
        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(FileInterface::class, $all->type());
        $this->assertSame(
            [$foo, $bar],
            unwrap($all),
        );
    }
}
