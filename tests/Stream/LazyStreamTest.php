<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\{
    Stream\LazyStream,
    Stream as StreamInterface
};
use PHPUnit\Framework\TestCase;

class LazyStreamTest extends TestCase
{
    private $stream;

    public function setUp()
    {
        file_put_contents(
            $path = tempnam(sys_get_temp_dir(), 'lazy_stream'),
            'lorem ipsum dolor'
        );
        $this->stream = new LazyStream($path);
    }

    public function testInterface()
    {
        $stream = new LazyStream('foo');

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertFalse($stream->isInitialized());
    }

    public function testDoesntInitializeWhenRewindingUninitializedStream()
    {
        $this->assertSame($this->stream, $this->stream->rewind());
        $this->assertFalse($this->stream->isInitialized());
    }

    public function testCast()
    {
        $this->assertSame('lorem ipsum dolor', (string) $this->stream);
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testClose()
    {
        $this->assertSame($this->stream, $this->stream->close());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testSize()
    {
        $this->assertSame(17, $this->stream->size());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testKnowsSize()
    {
        $this->assertTrue($this->stream->knowsSize());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testPosition()
    {
        $this->assertSame(0, $this->stream->position());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testIsEof()
    {
        $this->assertFalse($this->stream->isEof());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testSeek()
    {
        $this->assertSame($this->stream, $this->stream->seek(3));
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testRead()
    {
        $this->assertSame('lorem', $this->stream->read(5));
        $this->assertTrue($this->stream->isInitialized());
    }
}
