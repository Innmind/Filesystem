<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\{
    File\Content,
    Stream\LazyStream,
    Exception\FailedToLoadFile,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
    Str,
    Either,
    Maybe,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 */
final class OfStream implements Content, Chunkable
{
    /** @var callable(): Readable */
    private $load;

    /**
     * @param callable(): Readable $load
     */
    private function __construct(callable $load)
    {
        $this->load = $load;
    }

    /**
     * @psalm-pure
     */
    public static function of(Readable $stream): self
    {
        return new self(static fn() => $stream);
    }

    /**
     * @psalm-pure
     *
     * @param callable(): Readable $load
     */
    public static function lazy(callable $load): self
    {
        return new self($load);
    }

    public function foreach(callable $function): SideEffect
    {
        return $this->lines()->foreach($function);
    }

    public function map(callable $map): Content
    {
        return Lines::of($this->lines()->map($map));
    }

    public function flatMap(callable $map): Content
    {
        return Lines::of($this->lines())->flatMap($map);
    }

    public function filter(callable $filter): Content
    {
        return Lines::of($this->lines()->filter($filter));
    }

    public function lines(): Sequence
    {
        return $this
            ->sequence()
            ->map(static fn($line) => Line::fromStream($line));
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->lines()->reduce($carry, $reducer);
    }

    public function size(): Maybe
    {
        return $this->load()->size();
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
        return $this->sequence(static fn(Readable $stream) => $stream->read(8192));
    }

    /**
     * @param ?callable(Readable): Maybe<Str> $read
     *
     * @return Sequence<Str>
     */
    private function sequence(callable $read = null): Sequence
    {
        /** @var callable(Readable): Maybe<Str> */
        $read ??= static fn(Readable $stream): Maybe => $stream->readLine();

        return Sequence::lazy(function($cleanup) use ($read) {
            $stream = $this->load();
            $rewind = static function() use ($stream): void {
                $_ = $stream->rewind()->match(
                    static fn() => null, // rewind successfull
                    static fn() => throw new FailedToLoadFile,
                );
            };
            $cleanup($rewind);
            /** @var Readable */
            $stream = $stream->rewind()->match(
                static fn($stream) => $stream,
                static fn() => throw new FailedToLoadFile,
            );

            while (!$stream->end()) {
                // we yield an empty line when the readLine() call doesn't return
                // anything otherwise it will fail to load empty files or files
                // ending with the "end of line" character
                yield $read($stream)->match(
                    static fn($line) => $line,
                    static fn() => match ($stream->end()) {
                        true => Str::of(''),
                        false => throw new FailedToLoadFile,
                    },
                );
            }

            $rewind();
        });
    }

    private function load(): Readable
    {
        /** @psalm-suppress ImpureFunctionCall */
        return ($this->load)();
    }
}
