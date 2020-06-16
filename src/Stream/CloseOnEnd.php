<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

use Innmind\Stream\{
    Stream,
    Readable,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
};
use Innmind\Immutable\Str;

final class CloseOnEnd implements Readable
{
    private Readable $stream;

    public function __construct(Readable $stream)
    {
        $this->stream = $stream;
    }

    public function close(): void
    {
        $this->stream->close();
    }

    public function closed(): bool
    {
        return $this->stream->closed();
    }

    public function position(): Position
    {
        return $this->stream->position();
    }

    public function seek(Position $position, Mode $mode = null): void
    {
        $this->stream->seek($position, $mode);
    }

    public function rewind(): void
    {
        $this->stream->rewind();
    }

    public function end(): bool
    {
        $end = $this->stream->end();

        if ($end) {
            $this->close();
        }

        return $end;
    }

    public function size(): Size
    {
        return $this->stream->size();
    }

    public function knowsSize(): bool
    {
        return $this->stream->knowsSize();
    }

    public function read(int $length = null): Str
    {
        return $this->stream->read($length);
    }

    public function readLine(): Str
    {
        return $this->stream->readLine();
    }

    public function toString(): string
    {
        return $this->stream->toString();
    }
}
