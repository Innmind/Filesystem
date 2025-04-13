<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\IO\{
    Streams\Stream,
    Frame,
};
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
    private function __construct(
        private Stream $io,
    ) {
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
            ->read()
            ->watch()
            ->frames(Frame::line())
            ->lazy()
            ->rewindable()
            ->sequence()
            ->map(Line::fromStream(...));
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->lines()->reduce($carry, $reducer);
    }

    public function size(): Maybe
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->io->read()->internal()->size();
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
            ->read()
            ->watch()
            ->frames(Frame::chunk(8192)->loose())
            ->lazy()
            ->rewindable()
            ->sequence();
    }
}
