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
use Innmind\Url\Path;
use Innmind\Immutable\Str;

final class LazyStream implements Readable
{
    private Path $path;
    private ?Readable $stream = null;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function close(): void
    {
        $this->stream()->close();
    }

    public function closed(): bool
    {
        return $this->stream()->closed();
    }

    public function position(): Position
    {
        return $this->stream()->position();
    }

    public function seek(Position $position, Mode $mode = null): void
    {
        $this->stream()->seek($position, $mode);
    }

    public function rewind(): void
    {
        if ($this->stream && !$this->stream->closed()) {
            // this trick allows to automatically close the opened files on the
            // system in order to avoid a fatal error when too many files are
            // opened. This is possible because of the rewind done in
            // Adapter\Filesystem::createFileAt() after persisting a file.
            // This does not break be behaviour of the streams as once the stream
            // is manually closed we won't reopen it here
            $this->stream->close();
            $this->stream = null;
        }
    }

    public function end(): bool
    {
        return $this->stream()->end();
    }

    public function size(): Size
    {
        return $this->stream()->size();
    }

    public function knowsSize(): bool
    {
        return $this->stream()->knowsSize();
    }

    public function read(int $length = null): Str
    {
        return $this->stream()->read($length);
    }

    public function readLine(): Str
    {
        return $this->stream()->readLine();
    }

    public function toString(): string
    {
        return $this->stream()->toString();
    }

    private function stream(): Readable
    {
        return $this->stream ?? $this->stream = Readable\Stream::open($this->path);
    }
}
