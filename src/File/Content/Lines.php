<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\{
    File\Content,
    Stream\NullStream,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;
use function Innmind\Immutable\join;

/**
 * @internal
 * @psalm-immutable
 */
final class Lines implements Content
{
    /** @var Sequence<Line> */
    private Sequence $lines;

    /**
     * @param Sequence<Line> $lines
     */
    private function __construct(Sequence $lines)
    {
        $this->lines = $lines;
    }

    /**
     * @param Sequence<Line> $lines
     */
    public static function of(Sequence $lines): self
    {
        return new self($lines);
    }

    public function foreach(callable $function): void
    {
        $_ = $this->lines->foreach($function);
    }

    public function map(callable $map): Content
    {
        return new self($this->lines->map($map));
    }

    public function filter(callable $filter): Content
    {
        return new self($this->lines->filter($filter));
    }

    public function transform(callable $map): Sequence
    {
        return $this->lines->map($map);
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->lines->reduce($carry, $reducer);
    }

    public function toString(): string
    {
        $lines = $this->lines->map(static fn($line) => $line->toString());

        return join("\n", $lines)->toString();
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
        return Readable\Stream::ofContent($this->toString());
    }
}
