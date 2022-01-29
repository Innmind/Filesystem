<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

use Innmind\Stream\{
    Stream,
    Readable,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
    PositionNotSeekable,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
    SideEffect,
};

final class NullStream implements Readable
{
    private bool $closed = false;

    public function close(): Either
    {
        $this->closed = true;

        return Either::right(new SideEffect);
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        return $this->closed;
    }

    public function position(): Position
    {
        return new Position(0);
    }

    public function seek(Position $position, Mode $mode = null): Either
    {
        return Either::left(new PositionNotSeekable);
    }

    public function rewind(): Either
    {
        /** @var Either<PositionNotSeekable, Stream> */
        return Either::right($this);
    }

    public function end(): bool
    {
        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function size(): Maybe
    {
        return Maybe::just(new Size(0));
    }

    public function read(int $length = null): Maybe
    {
        if ($this->closed) {
            /** @var Maybe<Str> */
            return Maybe::nothing();
        }

        return Maybe::just(Str::of(''));
    }

    public function readLine(): Maybe
    {
        if ($this->closed) {
            /** @var Maybe<Str> */
            return Maybe::nothing();
        }

        return Maybe::just(Str::of(''));
    }

    public function toString(): Maybe
    {
        if ($this->closed) {
            /** @var Maybe<string> */
            return Maybe::nothing();
        }

        return Maybe::just('');
    }
}
