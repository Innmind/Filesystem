<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\{
    Stream\StringStream,
    StreamInterface
};

class StringStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $stream = new StringStream($lorem = 'Lorem ipsum dolor');

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertTrue($stream->knowsSize());
        $this->assertSame(17, $stream->size());
        $this->assertSame(0, $stream->position());
        $this->assertSame($stream, $stream->seek(6));
        $this->assertSame('ipsum', $stream->read(5));
        $this->assertFalse($stream->isEof());
        $stream->read(6);
        $this->assertSame($stream, $stream->rewind());
        $this->assertSame(0, $stream->position());
        $this->assertFalse($stream->isEof());
        $this->assertSame($lorem, (string) $stream);
        $this->assertSame($stream, $stream->close());
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\PositionNotSeekableException
     */
    public function testThrowWhenPositionNotSeekable()
    {
        $stream = new StringStream('Lorem ipsum dolor');

        $stream->seek(200);
    }
}
