<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\Logger,
    Adapter\InMemory,
    File,
    File\Content,
    Name,
};
use Psr\Log\NullLogger;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Adapter::class,
            Logger::psr(
                InMemory::new(),
                new NullLogger,
            ),
        );
    }

    public function testAdd()
    {
        $adapter = Logger::psr(
            $inner = InMemory::new(),
            new NullLogger,
        );
        $file = File::of(Name::of('foo'), Content::none());

        $this->assertNull($adapter->add($file));
        $this->assertTrue($inner->contains($file->name()));
    }

    public function testGet()
    {
        $adapter = Logger::psr(
            $inner = InMemory::new(),
            new NullLogger,
        );
        $name = Name::of('foo');
        $file = File::of($name, Content::none());
        $inner->add($file);

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
            $inner = InMemory::new(),
            new NullLogger,
        );
        $name = Name::of('foo');
        $inner->add(File::of($name, Content::none()));

        $this->assertTrue($adapter->contains($name));
    }

    public function testRemove()
    {
        $adapter = Logger::psr(
            $inner = InMemory::new(),
            new NullLogger,
        );
        $name = Name::of('foo');
        $inner->add(File::of($name, Content::none()));

        $this->assertNull($adapter->remove($name));
        $this->assertFalse($inner->contains($name));
    }

    public function testRoot()
    {
        $adapter = Logger::psr(
            $inner = InMemory::new(),
            new NullLogger,
        );
        $file = File::named(
            'watev',
            Content::none(),
        );
        $inner->add($file);

        $this->assertSame([$file], $adapter->root()->all()->toList());
    }
}
