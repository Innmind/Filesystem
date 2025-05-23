<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\IO\Stream\Size;
use Innmind\Immutable\{
    Sequence,
    Str,
    SideEffect,
    Maybe,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Lines implements Implementation
{
    /** @var Sequence<Line> */
    private Sequence $lines;

    /**
     * @param Sequence<Line> $lines
     */
    private function __construct(Sequence $lines)
    {
        $this->lines = $lines->pad(1, Line::of(Str::of('')));
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Line> $lines
     */
    public static function of(Sequence $lines): self
    {
        return new self($lines);
    }

    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        return $this->lines->foreach($function);
    }

    #[\Override]
    public function map(callable $map): Implementation
    {
        return new self($this->lines->map($map));
    }

    #[\Override]
    public function flatMap(callable $map): Implementation
    {
        return new self($this->lines->flatMap(
            static fn($line) => $map($line)->lines(),
        ));
    }

    #[\Override]
    public function filter(callable $filter): Implementation
    {
        return new self($this->lines->filter($filter));
    }

    #[\Override]
    public function lines(): Sequence
    {
        return $this->lines;
    }

    #[\Override]
    public function chunks(): Sequence
    {
        $firstLineRead = false;

        return $this->lines->map(static function($line) use (&$firstLineRead) {
            /** @psalm-suppress RedundantCondition */
            if (!$firstLineRead) {
                $firstLineRead = true;

                return $line->str();
            }

            return $line->str()->prepend("\n");
        });
    }

    #[\Override]
    public function reduce($carry, callable $reducer)
    {
        return $this->lines->reduce($carry, $reducer);
    }

    #[\Override]
    public function size(): Maybe
    {
        // we compute the size line by line to avoid loading the whole file in memory
        $size = $this->lines->reduce(
            0,
            static fn(int $size, Line $line): int => $size + $line->str()->toEncoding(Str\Encoding::ascii)->length() + 1, // the 1 is for the "end of line" character
        );
        // 1 is removed from the size because the last line won't have the
        // "end of line" character
        $size = \max(0, $size - 1);

        return Maybe::just(Size::of($size));
    }

    #[\Override]
    public function toString(): string
    {
        $lines = $this->lines->map(static fn($line) => $line->toString());

        return Str::of("\n")->join($lines)->toString();
    }
}
