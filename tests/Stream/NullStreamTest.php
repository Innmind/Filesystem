<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\{
    Stream\NullStream,
    Stream as StreamInterface,
    Exception\PositionNotSeekableException
};
use PHPUnit\Framework\TestCase;

class NullStreamTest extends TestCase
{
    public function testInterface()
    {
        $stream = new NullStream;

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame('', (string) $stream);
        $this->assertSame($stream, $stream->close());
        $this->assertTrue($stream->knowsSize());
        $this->assertSame(0, $stream->size());
        $this->assertSame(0, $stream->position());
        $this->assertTrue($stream->isEof());
        $this->assertSame($stream, $stream->rewind());
        $this->assertSame('', $stream->read(42));
        $this->expectException(PositionNotSeekableException::class);
        $stream->seek(42);
    }
}
