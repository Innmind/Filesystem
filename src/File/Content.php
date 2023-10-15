<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\File\Content\{
    Implementation,
    Line,
};
use Innmind\Stream\{
    Stream\Size,
    Readable,
    Capabilities,
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
    public static function atPath(Path $path, Capabilities\Readable $capabilities = null): self
    {
        return new self(Content\AtPath::of($path, $capabilities));
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
     * @psalm-pure
     */
    public static function ofStream(Readable $stream): self
    {
        return new self(Content\OfStream::lazy(static fn() => $stream));
    }

    /**
     * @psalm-pure
     *
     * @param callable(): Readable $load
     */
    public static function lazy(callable $load): self
    {
        return new self(Content\OfStream::lazy($load));
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
