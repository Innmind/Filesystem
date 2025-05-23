<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\File\Content\{
    Implementation,
    Line,
};
use Innmind\IO\{
    IO,
    Streams\Stream,
    Files\Read,
    Stream\Size,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Sequence,
    SideEffect,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Content
{
    private Implementation $implementation;

    private function __construct(Implementation $implementation)
    {
        $this->implementation = $implementation;
    }

    /**
     * @psalm-pure
     */
    public static function atPath(
        IO $io,
        Path $path,
    ): self {
        return new self(Content\AtPath::of($io, $path));
    }

    /**
     * @psalm-pure
     */
    public static function io(Stream|Read $io): self
    {
        return new self(Content\IO::of($io));
    }

    /**
     * @psalm-pure
     *
     * This method is to be used with sockets that can't be read twice
     */
    public static function oneShot(Stream $io): self
    {
        return new self(Content\OneShot::of($io));
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Str> $chunks
     */
    public static function ofChunks(Sequence $chunks): self
    {
        return new self(Content\Chunks::of($chunks));
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Line> $lines
     */
    public static function ofLines(Sequence $lines): self
    {
        return new self(Content\Lines::of($lines));
    }

    /**
     * @psalm-pure
     */
    public static function ofString(string $content): self
    {
        return self::ofLines(
            Str::of($content)
                ->split("\n")
                ->map(static fn($line) => Line::fromStream($line)),
        );
    }

    /**
     * @psalm-pure
     */
    public static function none(): self
    {
        return new self(Content\Lines::of(Sequence::of(Line::of(Str::of('')))));
    }

    /**
     * @param callable(Line): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        return $this->implementation->foreach($function);
    }

    /**
     * @param callable(Line): Line $map
     */
    public function map(callable $map): self
    {
        return new self($this->implementation->map($map));
    }

    /**
     * @param callable(Line): self $map
     */
    public function flatMap(callable $map): self
    {
        return new self($this->implementation->flatMap($map));
    }

    /**
     * @param callable(Line): bool $filter
     */
    public function filter(callable $filter): self
    {
        return new self($this->implementation->filter($filter));
    }

    /**
     * @return Sequence<Line>
     */
    public function lines(): Sequence
    {
        return $this->implementation->lines();
    }

    /**
     * @return Sequence<Str>
     */
    public function chunks(): Sequence
    {
        return $this->implementation->chunks();
    }

    /**
     * @template T
     *
     * @param T $carry
     * @param callable(T, Line): T $reducer
     *
     * @return T
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->implementation->reduce($carry, $reducer);
    }

    /**
     * @return Maybe<Size>
     */
    public function size(): Maybe
    {
        return $this->implementation->size();
    }

    public function toString(): string
    {
        return $this->implementation->toString();
    }
}
