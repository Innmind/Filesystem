<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Adapter;

use Innmind\Filesystem\{
    Adapter\LazyAdapter,
    Adapter\MemoryAdapter,
    LazyAdapterInterface,
    Directory
};

class LazyAdapterTest extends \PHPUnit_Framework_TestCase
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
     * @expectedException Innmind\Filesystem\Exception\FileNotFoundException
     */
    public function testThrowWhenGettingUnknwonFile()
    {
        $l = new LazyAdapter(new MemoryAdapter);

        $l->get('foo');
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFoundException
     */
    public function testThrowWhenRemovingUnknwonFile()
    {
        $l = new LazyAdapter(new MemoryAdapter);

        $l->remove('foo');
    }
}