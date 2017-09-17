<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

use Innmind\Filesystem\Exception\InvalidArgumentException;
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
    private $path;
    private $stream;

    public function __construct(string $path)
    {
        if (empty($path)) {
            throw new InvalidArgumentException;
        }

        $this->path = $path;
    }

    public function close(): Stream
    {
        $this->stream()->close();

        return $this;
    }

    public function closed(): bool
    {
        return $this->stream()->closed();
    }

    public function position(): Position
    {
        return $this->stream()->position();
    }

    public function seek(Position $position, Mode $mode = null): Stream
    {
        $this->stream()->seek($position, $mode);

        return $this;
    }

    public function rewind(): Stream
    {
        if ($this->isInitialized()) {
            $this->stream()->rewind();
        }

        return $this;
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

    public function __toString(): string
    {
        return (string) $this->stream();
    }

    public function isInitialized(): bool
    {
        return $this->stream instanceof Readable;
    }

    private function stream()
    {
        if (!$this->isInitialized()) {
            $this->stream = new Readable\Stream(fopen($this->path, 'r'));
        }

        return $this->stream;
    }
}
