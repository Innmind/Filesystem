<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

use Innmind\Stream\{
    Stream,
    Readable,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
    Exception\PositionNotSeekable,
};
use Innmind\Immutable\Str;

final class NullStream implements Readable
{
    private bool $closed = false;

    public function close(): void
    {
        $this->closed = true;
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    public function position(): Position
    {
        return new Position(0);
    }

    public function seek(Position $position, Mode $mode = null): void
    {
        throw new PositionNotSeekable;
    }

    public function rewind(): void
    {
    }

    public function end(): bool
    {
        return true;
    }

    public function size(): Size
    {
        return new Size(0);
    }

    public function knowsSize(): bool
    {
        return true;
    }

    public function read(int $length = null): Str
    {
        return Str::of('');
    }

    public function readLine(): Str
    {
        return Str::of('');
    }

    public function toString(): string
    {
        return '';
    }
}
