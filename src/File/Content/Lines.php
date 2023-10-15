<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\File\Content;
use Innmind\Stream\Stream\Size;
use Innmind\Immutable\{
    Sequence,
    Str,
    SideEffect,
    Maybe,
};

/**
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
     * @psalm-pure
     *
     * @param Sequence<Line> $lines
     */
    public static function of(Sequence $lines): self
    {
        return new self($lines);
    }

    /**
     * @psalm-pure
     */
    public static function ofContent(string $content): self
    {
        return new self(
            Str::of($content)
                ->split("\n")
                ->map(static fn($line) => Line::fromStream($line)),
        );
    }

    public function foreach(callable $function): SideEffect
    {
        return $this->lines->foreach($function);
    }

    public function map(callable $map): Content
    {
        return new self($this->lines->map($map));
    }

    public function flatMap(callable $map): Content
    {
        return new self($this->lines->flatMap(
            static fn($line) => $map($line)->lines(),
        ));
    }

    public function filter(callable $filter): Content
    {
        return new self($this->lines->filter($filter));
    }

    public function lines(): Sequence
    {
        return $this->lines;
    }

    public function chunks(): Sequence
    {
        $firstLineRead = false;

        return $this->lines->map(static function($line) use (&$firstLineRead) {
            if (!$firstLineRead) {
                $firstLineRead = true;

                return $line->str();
            }

            return $line->str()->prepend("\n");
        });
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->lines->reduce($carry, $reducer);
    }

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

        return Maybe::just(new Size($size));
    }

    public function toString(): string
    {
        $lines = $this->lines->map(static fn($line) => $line->toString());

        return Str::of("\n")->join($lines)->toString();
    }
}
