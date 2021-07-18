<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\{
    File\Content,
    Stream\LazyStream,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class AtPath implements Content
{
    private Path $path;

    private function __construct(Path $path)
    {
        $this->path = $path;
    }

    public static function of(Path $path): self
    {
        return new self($path);
    }

    public function foreach(callable $function): void
    {
        $_ = $this->sequence()->foreach($function);
    }

    public function map(callable $map): Content
    {
        return Lines::of($this->transform($map));
    }

    public function filter(callable $filter): Content
    {
        return Lines::of($this->sequence()->filter($filter));
    }

    public function transform(callable $map): Sequence
    {
        return $this->sequence()->map($map);
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->sequence()->reduce($carry, $reducer);
    }

    public function toString(): string
    {
        $stream = $this->stream();
        /** @psalm-suppress ImpureMethodCall */
        $content = $stream->toString();
        /** @psalm-suppress ImpureMethodCall */
        $stream->close();

        return $content;
    }

    /**
     * This method should be use with extreme care as manipulating a stream
     * manually can lead to unexpected behaviour in your code.
     *
     * Implementations of this interface should always return a different
     * instance of the stream object to avoid side effects in the implementation
     */
    public function stream(): Readable
    {
        return new LazyStream($this->path);
    }

    /**
     * @return Sequence<Line>
     */
    private function sequence(): Sequence
    {
        return Sequence::lazy(function() {
            $stream = $this->stream();

            while (!$stream->end()) {
                $line = $stream->readLine();

                yield Line::fromStream($line);
            }

            $stream->close();
        });
    }
}
