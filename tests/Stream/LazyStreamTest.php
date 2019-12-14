<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\Stream\LazyStream;
use Innmind\Stream\{
    Readable,
    Stream\Position
};
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
        $this->stream = new LazyStream($path);
    }

    public function testInterface()
    {
        $stream = new LazyStream('foo');

        $this->assertInstanceOf(Readable::class, $stream);
        $this->assertFalse($stream->isInitialized());
    }

    public function testDoesntInitializeWhenRewindingUninitializedStream()
    {
        $this->assertNull($this->stream->rewind());
        $this->assertFalse($this->stream->isInitialized());
    }

    public function testCast()
    {
        $this->assertSame('lorem ipsum dolor', $this->stream->toString());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testClose()
    {
        $this->assertNull($this->stream->close());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testSize()
    {
        $this->assertSame(17, $this->stream->size()->toInt());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testKnowsSize()
    {
        $this->assertTrue($this->stream->knowsSize());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testPosition()
    {
        $this->assertSame(0, $this->stream->position()->toInt());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testEnd()
    {
        $this->assertFalse($this->stream->end());
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testSeek()
    {
        $this->assertNull($this->stream->seek(new Position(3)));
        $this->assertTrue($this->stream->isInitialized());
    }

    public function testRead()
    {
        $this->assertSame('lorem', $this->stream->read(5)->toString());
        $this->assertTrue($this->stream->isInitialized());
    }
}
