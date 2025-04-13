<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\IO\Stream\Size;
use Innmind\Validation\Is;
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

    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        return $this->content()->foreach($function);
    }

    #[\Override]
    public function map(callable $map): Implementation
    {
        return $this->content()->map($map);
    }

    #[\Override]
    public function flatMap(callable $map): Implementation
    {
        return $this->content()->flatMap($map);
    }

    #[\Override]
    public function filter(callable $filter): Implementation
    {
        return $this->content()->filter($filter);
    }

    #[\Override]
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

    #[\Override]
    public function chunks(): Sequence
    {
        return $this->chunks;
    }

    #[\Override]
    public function reduce($carry, callable $reducer)
    {
        return $this->lines()->reduce($carry, $reducer);
    }

    #[\Override]
    public function size(): Maybe
    {
        return Maybe::just(
            $this
                ->chunks
                ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
                ->map(static fn($chunk) => $chunk->length())
                ->reduce(
                    0,
                    static fn(int $total, int $chunk) => $total + $chunk,
                ),
        )
            ->keep(
                Is::value(0)
                    ->or(Is::int()->positive())
                    ->asPredicate(),
            )
            ->map(Size::of(...));
    }

    #[\Override]
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
