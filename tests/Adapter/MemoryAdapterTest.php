<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\MemoryAdapter,
    Adapter,
    Directory\Directory,
    File as FileInterface,
    File\File,
    Name\Name,
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
        $this->assertFalse($a->contains(new Name('foo')));
        $this->assertNull(
            $a->add($d = new Directory('foo'))
        );
        $this->assertTrue($a->contains(new Name('foo')));
        $this->assertSame($d, $a->get(new Name('foo')));
        $this->assertNull($a->remove(new Name('foo')));
        $this->assertFalse($a->contains(new Name('foo')));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        (new MemoryAdapter)->get(new Name('foo'));
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertNull((new MemoryAdapter)->remove(new Name('foo')));
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
