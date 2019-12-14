<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

use Innmind\Filesystem\Exception\DomainException;
use Innmind\Stream\{
    Stream,
    Readable,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size
};
use Innmind\Immutable\Str;

final class LazyStream implements Readable
{
    private string $path;
    private ?Readable $stream = null;

    public function __construct(string $path)
    {
        if (empty($path)) {
            throw new DomainException;
        }

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
        if ($this->isInitialized()) {
            $this->stream()->rewind();
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

    public function isInitialized(): bool
    {
        return $this->stream instanceof Readable;
    }

    private function stream()
    {
        if (!$this->isInitialized()) {
            $this->stream = new Readable\Stream(\fopen($this->path, 'r'));
        }

        return $this->stream;
    }
}
