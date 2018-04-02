<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\LazyAdapter,
    Adapter\MemoryAdapter,
    LazyAdapter as LazyAdapterInterface,
    Directory\Directory,
    File as FileInterface,
    File\File,
    Stream\StringStream
};
use Innmind\Immutable\MapInterface;
use PHPUnit\Framework\TestCase;

class LazyAdapterTest extends TestCase
{
    public function testInterface()
    {
        $l = new LazyAdapter($a = new MemoryAdapter);

        $this->assertInstanceOf(LazyAdapterInterface::class, $l);
        $this->assertFalse($l->has('foo'));
        $this->assertSame(
            $l,
            $l->add($d = new Directory('foo'))
        );
        $this->assertTrue($l->has('foo'));
        $this->assertFalse($a->has('foo'));
        $this->assertSame($l, $l->persist());
        $this->assertTrue($l->has('foo'));
        $this->assertTrue($a->has('foo'));
        $this->assertSame($l, $l->remove('foo'));
        $this->assertFalse($l->has('foo'));
        $this->assertTrue($a->has('foo'));
        $l->persist();
        $this->assertFalse($l->has('foo'));
        $this->assertFalse($a->has('foo'));
    }

    public function testRemoveUnpersistedAddedFile()
    {
        $l = new LazyAdapter($a = new MemoryAdapter);

        $l
            ->add(new Directory('foo'))
            ->remove('foo')
            ->persist();
        $this->assertFalse($l->has('foo'));
        $this->assertFalse($a->has('foo'));
    }

    public function testAddUnpersistedRemovedFile()
    {
        $l = new LazyAdapter($a = new MemoryAdapter);

        $a->add(new Directory('foo'));
        $l
            ->remove('foo')
            ->add($d = new Directory('foo'))
            ->persist();
        $this->assertTrue($l->has('foo'));
        $this->assertTrue($a->has('foo'));
        $this->assertSame($d, $l->get('foo'));
        $this->assertSame($d, $a->get('foo'));
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFound
     * @expectedExceptionMessage foo
     */
    public function testThrowWhenGettingUnknwonFile()
    {
        $l = new LazyAdapter(new MemoryAdapter);

        $l->get('foo');
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFound
     * @expectedExceptionMessage foo
     */
    public function testThrowWhenRemovingUnknwonFile()
    {
        $l = new LazyAdapter(new MemoryAdapter);

        $l->remove('foo');
    }

    public function testAll()
    {
        $memory = new MemoryAdapter;
        $lazy = new LazyAdapter($memory);
        $memory->add(new File('foo', new StringStream('')));
        $lazy->remove('foo');
        $lazy->add($bar = new File('bar', new StringStream('')));

        $all = $lazy->all();
        $this->assertInstanceOf(MapInterface::class, $all);
        $this->assertSame('string', (string) $all->keyType());
        $this->assertSame(FileInterface::class, (string) $all->valueType());
        $this->assertCount(1, $all);
        $this->assertSame($bar, $all->get('bar'));
    }
}
