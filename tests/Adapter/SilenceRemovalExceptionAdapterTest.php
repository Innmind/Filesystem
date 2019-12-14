<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\SilenceRemovalExceptionAdapter,
    Adapter,
    File,
    Exception\FileNotFound
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class SilenceRemovalExceptionAdapterTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Adapter::class,
            new SilenceRemovalExceptionAdapter(
                $this->createMock(Adapter::class)
            )
        );
    }

    public function testAdd()
    {
        $adapter = new SilenceRemovalExceptionAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $file = $this->createMock(File::class);
        $inner
            ->expects($this->once())
            ->method('add')
            ->with($file);

        $this->assertNull($adapter->add($file));
    }

    public function testGet()
    {
        $adapter = new SilenceRemovalExceptionAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $file = $this->createMock(File::class);
        $inner
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($file);

        $this->assertSame($file, $adapter->get('foo'));
    }

    public function testContains()
    {
        $adapter = new SilenceRemovalExceptionAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $inner
            ->expects($this->at(0))
            ->method('contains')
            ->with('foo')
            ->willReturn(true);
        $inner
            ->expects($this->at(1))
            ->method('contains')
            ->with('foo')
            ->willReturn(false);

        $this->assertTrue($adapter->contains('foo'));
        $this->assertFalse($adapter->contains('foo'));
    }

    public function testRemove()
    {
        $adapter = new SilenceRemovalExceptionAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $inner
            ->expects($this->once())
            ->method('remove')
            ->with('foo');

        $this->assertNull($adapter->remove('foo'));
    }

    public function testRemoveSafelyWhenFileNotFound()
    {
        $adapter = new SilenceRemovalExceptionAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $inner
            ->expects($this->once())
            ->method('remove')
            ->with('foo')
            ->will(
                $this->throwException(new FileNotFound)
            );

        $this->assertNull($adapter->remove('foo'));
    }

    public function testAll()
    {
        $adapter = new SilenceRemovalExceptionAdapter(
            $inner = $this->createMock(Adapter::class)
        );
        $inner
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                $all = Map::of('string', File::class)
            );

        $this->assertSame($all, $adapter->all());
    }
}
