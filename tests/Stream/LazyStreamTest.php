<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\Stream\LazyStream;
use Innmind\Stream\{
    Readable,
    Stream\Position,
};
use Innmind\Url\Path;
use Innmind\Immutable\SideEffect;
use PHPUnit\Framework\TestCase;

class LazyStreamTest extends TestCase
{
    private $stream;

    public function setUp(): void
    {
        \file_put_contents(
            $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream'),
            'lorem ipsum dolor',
        );
        $stream = new LazyStream(Path::of($path));
    }

    public function testInterface()
    {
        $stream = new LazyStream(Path::of('foo'));

        $this->assertInstanceOf(Readable::class, $stream);
    }

    public function testDoesntInitializeWhenRewindingUninitializedStream()
    {
        $stream = new LazyStream(Path::of(
            \tempnam(\sys_get_temp_dir(), 'lazy_stream'),
        ));

        $this->assertSame(
            $stream,
            $stream->rewind()->match( // it would fail if initialized here as the file doesn't exist
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testCast()
    {
        $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        \file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame('lorem ipsum dolor', $stream->toString()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testClose()
    {
        $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        \file_put_contents($path, 'lorem ipsum dolor');

        $this->assertInstanceOf(
            SideEffect::class,
            $stream->close()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testSize()
    {
        $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        \file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame(17, $stream->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));
    }

    public function testPosition()
    {
        $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        \file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame(0, $stream->position()->toInt());
    }

    public function testEnd()
    {
        $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        \file_put_contents($path, 'lorem ipsum dolor');

        $this->assertFalse($stream->end());
    }

    public function testSeek()
    {
        $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        \file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame(
            $stream,
            $stream->seek(new Position(3))->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testRead()
    {
        $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        \file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame('lorem', $stream->read(5)->match(
            static fn($value) => $value->toString(),
            static fn() => null,
        ));
    }
}
