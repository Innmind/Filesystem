<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Sequence,
    Str,
    SideEffect,
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
            static fn($line) => $map($line)->transform(
                static fn(Line $line) => $line,
            ),
        ));
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

        return Str::of("\n")->join($lines)->toString();
    }
}
