<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\Exception\FailedToLoadFile;
use Innmind\IO;
use Innmind\Stream\Capabilities;
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
    private Capabilities\Readable $capabilities;
    private IO\Readable $io;
    private Path $path;

    private function __construct(
        Capabilities\Readable $capabilities,
        IO\Readable $io,
        Path $path,
    ) {
        $this->capabilities = $capabilities;
        $this->io = $io;
        $this->path = $path;
    }

    /**
     * @psalm-pure
     */
    public static function of(
        Capabilities\Readable $capabilities,
        IO\Readable $io,
        Path $path,
    ): self {
        return new self($capabilities, $io, $path);
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
        return Sequence::lazy(function($register) {
            $stream = $this->capabilities->open($this->path);
            $register(static fn() => $stream->close()->match(
                static fn() => null,
                static fn() => throw new FailedToLoadFile,
            ));
            $io = $this->io->wrap($stream);

            yield $io
                ->watch()
                ->lines()
                ->lazy()
                ->sequence();

            $stream->close()->match(
                static fn() => null,
                static fn() => throw new FailedToLoadFile,
            );
        })
            ->flatMap(static fn($lines) => $lines)
            ->map(static fn($line) => Line::fromStream($line));
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->lines()->reduce($carry, $reducer);
    }

    public function size(): Maybe
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this
            ->capabilities
            ->open($this->path)
            ->size();
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
        return Sequence::lazy(function($register) {
            $stream = $this->capabilities->open($this->path);
            $register(static fn() => $stream->close()->match(
                static fn() => null,
                static fn() => throw new FailedToLoadFile,
            ));
            $io = $this->io->wrap($stream);

            yield $io
                ->watch()
                ->chunks(8192)
                ->lazy()
                ->sequence();

            $stream->close()->match(
                static fn() => null,
                static fn() => throw new FailedToLoadFile,
            );
        })->flatMap(static fn($chunks) => $chunks);
    }
}
