<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\IO\Readable\Stream;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
    Maybe,
    Monoid\Concat,
};

/**
 * @internal
 * @psalm-immutable
 */
final class IO implements Implementation
{
    private Stream $io;

    private function __construct(Stream $io)
    {
        $this->io = $io;
    }

    /**
     * @psalm-pure
     */
    public static function of(Stream $io): self
    {
        return new self($io);
    }

    public function foreach(callable $function): SideEffect
    {
        return $this->lines()->foreach($function);
    }

    public function map(callable $map): Implementation
    {
        return Lines::of($this->lines()->map($map));
    }

    public function flatMap(callable $map): Implementation
    {
        return Lines::of($this->lines())->flatMap($map);
    }

    public function filter(callable $filter): Implementation
    {
        return Lines::of($this->lines()->filter($filter));
    }

    public function lines(): Sequence
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this
            ->io
            ->lines()
            ->lazy()
            ->rewindable()
            ->sequence()
            ->map(static fn($line) => Line::fromStream($line));
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->lines()->reduce($carry, $reducer);
    }

    public function size(): Maybe
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->io->size();
    }

    public function toString(): string
    {
        return $this
            ->chunks()
            ->fold(new Concat)
            ->toString();
    }

    public function chunks(): Sequence
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this
            ->io
            ->chunks(8192)
            ->lazy()
            ->rewindable()
            ->sequence();
    }
}
