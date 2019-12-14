<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\Stream\NullStream;
use Innmind\Stream\{
    Readable,
    Stream\Position,
    Exception\PositionNotSeekable
};
use PHPUnit\Framework\TestCase;

class NullStreamTest extends TestCase
{
    public function testInterface()
    {
        $stream = new NullStream;

        $this->assertInstanceOf(Readable::class, $stream);
        $this->assertSame('', $stream->toString());
        $this->assertNull($stream->close());
        $this->assertTrue($stream->knowsSize());
        $this->assertSame(0, $stream->size()->toInt());
        $this->assertSame(0, $stream->position()->toInt());
        $this->assertTrue($stream->end());
        $this->assertNull($stream->rewind());
        $this->assertSame('', $stream->read(42)->toString());
        $this->expectException(PositionNotSeekable::class);
        $stream->seek(new Position(42));
    }
}
