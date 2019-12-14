<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\Stream\StringStream;
use Innmind\Stream\{
    Readable,
    Stream\Position
};
use PHPUnit\Framework\TestCase;

class StringStreamTest extends TestCase
{
    public function testInterface()
    {
        $stream = new StringStream($lorem = 'Lorem ipsum dolor');

        $this->assertInstanceOf(Readable::class, $stream);
        $this->assertTrue($stream->knowsSize());
        $this->assertSame(17, $stream->size()->toInt());
        $this->assertSame(0, $stream->position()->toInt());
        $this->assertNull($stream->seek(new Position(6)));
        $this->assertSame('ipsum', $stream->read(5)->toString());
        $this->assertFalse($stream->end());
        $stream->read(6);
        $this->assertNull($stream->rewind());
        $this->assertSame(0, $stream->position()->toInt());
        $this->assertFalse($stream->end());
        $this->assertSame($lorem, $stream->toString());
        $this->assertNull($stream->close());
    }
}
