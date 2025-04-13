<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    Adapter\Logger,
    Adapter\InMemory,
    File,
    File\Content,
    Name,
};
use Innmind\Immutable\SideEffect;
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

        $this->assertInstanceOf(
            SideEffect::class,
            $adapter
                ->add($file)
                ->unwrap(),
        );
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
        $inner
            ->add($file)
            ->unwrap();

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
        $inner
            ->add(File::of($name, Content::none()))
            ->unwrap();

        $this->assertTrue($adapter->contains($name));
    }

    public function testRemove()
    {
        $adapter = Logger::psr(
            $inner = InMemory::new(),
            new NullLogger,
        );
        $name = Name::of('foo');
        $inner
            ->add(File::of($name, Content::none()))
            ->unwrap();

        $this->assertInstanceOf(
            SideEffect::class,
            $adapter
                ->remove($name)
                ->unwrap(),
        );
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
        $inner
            ->add($file)
            ->unwrap();

        $this->assertSame([$file], $adapter->root()->all()->toList());
    }
}
