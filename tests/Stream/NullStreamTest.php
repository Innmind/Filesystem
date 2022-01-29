<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\Stream\NullStream;
use Innmind\Stream\{
    Readable,
    Stream\Position,
    PositionNotSeekable,
};
use Innmind\Immutable\SideEffect;
use PHPUnit\Framework\TestCase;

class NullStreamTest extends TestCase
{
    public function testInterface()
    {
        $stream = new NullStream;

        $this->assertInstanceOf(Readable::class, $stream);
        $this->assertSame('', $stream->toString()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertInstanceOf(
            SideEffect::class,
            $stream->close()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(0, $stream->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));
        $this->assertSame(0, $stream->position()->toInt());
        $this->assertTrue($stream->end());
        $this->assertSame(
            $stream,
            $stream->rewind()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertNull($stream->read(42)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertInstanceOf(
            PositionNotSeekable::class,
            $stream->seek(new Position(42))->match(
                static fn() => null,
                static fn($error) => $error,
            ),
        );
    }
}
