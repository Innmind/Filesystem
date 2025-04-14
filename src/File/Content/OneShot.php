<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\Exception\LogicException;
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
final class OneShot implements Implementation
{
    private Stream $io;
    private bool $loaded = false;

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
        return Sequence::lazy(function() {
            $this->guard();

            yield $this
                ->io
                ->read()
                ->watch()
                ->frames(Frame::line())
                ->lazy()
                ->sequence();
        })
            ->flatMap(static fn($lines) => $lines)
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
        return Sequence::lazy(function() {
            $this->guard();

            yield $this
                ->io
                ->read()
                ->watch()
                ->frames(Frame::chunk(8192)->loose())
                ->lazy()
                ->sequence();
        })->flatMap(static fn($chunks) => $chunks);
    }

    private function guard(): void
    {
        if ($this->loaded) {
            throw new LogicException("Content can't be loaded twice");
        }

        /** @psalm-suppress InaccessibleProperty */
        $this->loaded = true;
    }
}
