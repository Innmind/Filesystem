<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\IO\{
    Streams\Stream,
    Files\Read,
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
        private Stream|Read $io,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Stream|Read $io): self
    {
        return new self($io);
    }

    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        return $this->lines()->foreach($function);
    }

    #[\Override]
    public function map(callable $map): Implementation
    {
        return Lines::of($this->lines()->map($map));
    }

    #[\Override]
    public function flatMap(callable $map): Implementation
    {
        return Lines::of($this->lines())->flatMap($map);
    }

    #[\Override]
    public function filter(callable $filter): Implementation
    {
        return Lines::of($this->lines()->filter($filter));
    }

    #[\Override]
    public function lines(): Sequence
    {
        if ($this->io instanceof Read) {
            /** @psalm-suppress ImpureMethodCall */
            return $this
                ->io
                ->watch()
                ->lines()
                ->map(Line::fromStream(...));
        }

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

    #[\Override]
    public function reduce($carry, callable $reducer)
    {
        return $this->lines()->reduce($carry, $reducer);
    }

    #[\Override]
    public function size(): Maybe
    {
        if ($this->io instanceof Read) {
            /** @psalm-suppress ImpureMethodCall */
            return $this->io->size();
        }

        /** @psalm-suppress ImpureMethodCall */
        return $this->io->read()->internal()->size();
    }

    #[\Override]
    public function toString(): string
    {
        return $this
            ->chunks()
            ->fold(new Concat)
            ->toString();
    }

    #[\Override]
    public function chunks(): Sequence
    {
        if ($this->io instanceof Read) {
            /** @psalm-suppress ImpureMethodCall */
            return $this
                ->io
                ->watch()
                ->chunks(8192);
        }

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
