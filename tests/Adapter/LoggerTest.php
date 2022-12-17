<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\Logger,
    Adapter,
    File,
    Name,
};
use Innmind\Immutable\{
    Set,
    Maybe,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Adapter::class,
            Logger::psr(
                $this->createMock(Adapter::class),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testAdd()
    {
        $adapter = Logger::psr(
            $inner = $this->createMock(Adapter::class),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $file = $this->createMock(File::class);
        $file
            ->method('name')
            ->willReturn(Name::of('foo'));
        $logger
            ->expects($this->once())
            ->method('debug');
        $inner
            ->expects($this->once())
            ->method('add')
            ->with($file);

        $this->assertNull($adapter->add($file));
    }

    public function testGet()
    {
        $adapter = Logger::psr(
            $inner = $this->createMock(Adapter::class),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $name = Name::of('foo');
        $file = $this->createMock(File::class);
        $file
            ->method('name')
            ->willReturn($name);
        $logger
            ->expects($this->once())
            ->method('debug');
        $inner
            ->expects($this->once())
            ->method('get')
            ->with($name)
            ->willReturn(Maybe::just($file));

        $this->assertSame(
            $file,
            $adapter->get($name)->match(
                static fn($file) => $file,
                static fn() => null,
            ),
        );
    }

    public function testContains()
    {
        $adapter = Logger::psr(
            $inner = $this->createMock(Adapter::class),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $name = Name::of('foo');
        $logger
            ->expects($this->once())
            ->method('debug');
        $inner
            ->expects($this->once())
            ->method('contains')
            ->with($name)
            ->willReturn(true);

        $this->assertTrue($adapter->contains($name));
    }

    public function testRemove()
    {
        $adapter = Logger::psr(
            $inner = $this->createMock(Adapter::class),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $name = Name::of('foo');
        $logger
            ->expects($this->once())
            ->method('debug');
        $inner
            ->expects($this->once())
            ->method('remove')
            ->with($name);

        $this->assertNull($adapter->remove($name));
    }

    public function testAll()
    {
        $adapter = Logger::psr(
            $inner = $this->createMock(Adapter::class),
            $this->createMock(LoggerInterface::class),
        );
        $all = Set::of(File::class);
        $inner
            ->expects($this->once())
            ->method('all')
            ->willReturn($all);

        $this->assertSame($all, $adapter->all());
    }
}
