<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Adapter;

use Innmind\Filesystem\{
    Adapter\MemoryAdapter,
    AdapterInterface,
    Directory
};

class MemoryAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $a = new MemoryAdapter;

        $this->assertInstanceOf(AdapterInterface::class, $a);
        $this->assertFalse($a->has('foo'));
        $this->assertSame(
            $a,
            $a->add($d = new Directory('foo'))
        );
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
        (new MemoryAdapter)->get('foo');
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\FileNotFoundException
     */
    public function testThrowWhenRemovingUnknownFile()
    {
        (new MemoryAdapter)->remove('foo');
    }
}
