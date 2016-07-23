<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\{
    Stream\Stream,
    StreamInterface
};

class StreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Innmind\Filesystem\Exception\InvalidArgumentException
     */
    public function testThrowWhenBuildingStreamWithoutResource()
    {
        new Stream('foo');
    }

    public function testInterface()
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $lorem = 'Lorem ipsum dolor');

        $stream = new Stream($resource);

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
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Lorem ipsum dolor');

        $stream = new Stream($resource);

        $stream->seek(200);
    }
}
