<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\MemoryAdapter,
    Adapter,
    Directory\Directory,
    File as FileInterface,
    File\File,
    Stream\StringStream
};
use Innmind\Immutable\Map;
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

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFound
     * @expectedExceptionMessage foo
     */
    public function testThrowWhenGettingUnknownFile()
    {
        (new MemoryAdapter)->get('foo');
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFound
     * @expectedExceptionMessage foo
     */
    public function testThrowWhenRemovingUnknownFile()
    {
        (new MemoryAdapter)->remove('foo');
    }

    public function testAll()
    {
        $adapter = new MemoryAdapter;
        $adapter->add($foo = new File(
            'foo',
            new StringStream('foo')
        ));
        $adapter->add($bar = new File(
            'bar',
            new StringStream('bar')
        ));

        $all = $adapter->all();
        $this->assertInstanceOf(Map::class, $all);
        $this->assertSame('string', (string) $all->keyType());
        $this->assertSame(FileInterface::class, (string) $all->valueType());
        $this->assertSame(
            ['foo', 'bar'],
            unwrap($all->keys())
        );
        $this->assertSame(
            [$foo, $bar],
            unwrap($all->values())
        );
    }
}
