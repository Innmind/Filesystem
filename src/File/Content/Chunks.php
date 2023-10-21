<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Immutable\{
    Sequence,
    SideEffect,
    Maybe,
    Str,
    Monoid\Concat,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Chunks implements Implementation
{
    /** @var Sequence<Str> */
    private Sequence $chunks;

    /**
     * @param Sequence<Str> $chunks
     */
    private function __construct(Sequence $chunks)
    {
        $this->chunks = $chunks->pad(1, Str::of(''));
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Str> $chunks
     */
    public static function of(Sequence $chunks): self
    {
        return new self($chunks);
    }

    public function foreach(callable $function): SideEffect
    {
        return $this->content()->foreach($function);
    }

    public function map(callable $map): Implementation
    {
        return $this->content()->map($map);
    }

    public function flatMap(callable $map): Implementation
    {
        return $this->content()->flatMap($map);
    }

    public function filter(callable $filter): Implementation
    {
        return $this->content()->filter($filter);
    }

    public function lines(): Sequence
    {
        // the flatMap is here in case there is only one chunk in which case the
        // aggregate won't be called
        return $this
            ->chunks
            ->aggregate(static fn(Str $a, Str $b) => $a->append($b->toString())->split("\n"))
            ->flatMap(static fn($chunk) => $chunk->split("\n"))
            ->map(Line::of(...));
    }

    public function chunks(): Sequence
    {
        return $this->chunks;
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->lines()->reduce($carry, $reducer);
    }

    public function size(): Maybe
    {
        return $this->content()->size();
    }

    public function toString(): string
    {
        return $this
            ->chunks
            ->fold(new Concat)
            ->toString();
    }

    private function content(): Implementation
    {
        return Lines::of($this->lines());
    }
}
