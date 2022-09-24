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
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

final class LazyStream implements Readable
{
    private Path $path;
    private ?Readable $stream = null;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function close(): Either
    {
        return $this->stream()->close();
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->stream()->closed();
    }

    public function position(): Position
    {
        return $this->stream()->position();
    }

    public function seek(Position $position, Mode $mode = null): Either
    {
        /** @var Either<PositionNotSeekable, Stream> */
        return $this->stream()->seek($position, $mode)->map(fn() => $this);
    }

    public function rewind(): Either
    {
        if ($this->stream && !$this->stream->closed()) {
            // this trick allows to automatically close the opened files on the
            // system in order to avoid a fatal error when too many files are
            // opened. This is possible because of the rewind done in
            // File\Content\OfStream::chunks() after persisting a file.
            // This does not break be behaviour of the streams as once the stream
            // is manually closed we won't reopen it here
            /** @var Either<PositionNotSeekable, Stream> */
            return $this
                ->stream
                ->close()
                ->map(function() {
                    $this->stream = null;

                    return $this;
                })
                ->leftMap(static fn() => new PositionNotSeekable);
        }

        /** @var Either<PositionNotSeekable, Stream> */
        return Either::right($this);
    }

    /**
     * @psalm-mutation-free
     */
    public function end(): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->stream()->end();
    }

    /**
     * @psalm-mutation-free
     */
    public function size(): Maybe
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->stream()->size();
    }

    public function read(int $length = null): Maybe
    {
        return $this->stream()->read($length);
    }

    public function readLine(): Maybe
    {
        return $this->stream()->readLine();
    }

    public function toString(): Maybe
    {
        return $this->stream()->toString();
    }

    private function stream(): Readable
    {
        return $this->stream ?? $this->stream = Readable\Stream::open($this->path);
    }
}
