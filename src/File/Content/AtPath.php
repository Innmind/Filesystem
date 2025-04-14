<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\IO\IO;
use Innmind\Url\Path;
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
final class AtPath implements Implementation
{
    private function __construct(
        private IO $io,
        private Path $path,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(IO $io, Path $path): self
    {
        return new self($io, $path);
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
        /** @psalm-suppress ImpureMethodCall */
        return $this
            ->io
            ->files()
            ->read($this->path)
            ->watch()
            ->lines()
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
        return $this
            ->io
            ->files()
            ->read($this->path)
            ->size();
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
        /** @psalm-suppress ImpureMethodCall */
        return $this
            ->io
            ->files()
            ->read($this->path)
            ->watch()
            ->chunks(8192);
    }
}
