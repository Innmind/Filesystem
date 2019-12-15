<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\Stream\LazyStream;
use Innmind\Stream\{
    Readable,
    Stream\Position,
};
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class LazyStreamTest extends TestCase
{
    private $stream;

    public function setUp(): void
    {
        file_put_contents(
            $path = tempnam(sys_get_temp_dir(), 'lazy_stream'),
            'lorem ipsum dolor'
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
            tempnam(sys_get_temp_dir(), 'lazy_stream'),
        ));

        $this->assertNull($stream->rewind()); // it would fail if initialized here as the file doesn't exist
    }

    public function testCast()
    {
        $path = tempnam(sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame('lorem ipsum dolor', $stream->toString());
    }

    public function testClose()
    {
        $path = tempnam(sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        file_put_contents($path, 'lorem ipsum dolor');

        $this->assertNull($stream->close());
    }

    public function testSize()
    {
        $path = tempnam(sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame(17, $stream->size()->toInt());
    }

    public function testKnowsSize()
    {
        $path = tempnam(sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        file_put_contents($path, 'lorem ipsum dolor');

        $this->assertTrue($stream->knowsSize());
    }

    public function testPosition()
    {
        $path = tempnam(sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame(0, $stream->position()->toInt());
    }

    public function testEnd()
    {
        $path = tempnam(sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        file_put_contents($path, 'lorem ipsum dolor');

        $this->assertFalse($stream->end());
    }

    public function testSeek()
    {
        $path = tempnam(sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        file_put_contents($path, 'lorem ipsum dolor');

        $this->assertNull($stream->seek(new Position(3)));
    }

    public function testRead()
    {
        $path = tempnam(sys_get_temp_dir(), 'lazy_stream');
        $stream = new LazyStream(Path::of($path));
        file_put_contents($path, 'lorem ipsum dolor');

        $this->assertSame('lorem', $stream->read(5)->toString());
    }
}
