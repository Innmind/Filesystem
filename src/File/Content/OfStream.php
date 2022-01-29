<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\{
    File\Content,
    Stream\LazyStream,
    Exception\FailedToLoadFile,
};
use Innmind\Stream\{
    Readable,
    PositionNotSeekable,
};
use Innmind\Immutable\{
    Sequence,
    SideEffect,
    Str,
    Either,
};

/**
 * @psalm-immutable
 */
final class OfStream implements Content
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
        return Lines::of($this->transform($map));
    }

    public function flatMap(callable $map): Content
    {
        return Lines::of($this->lines())->flatMap($map);
    }

    public function filter(callable $filter): Content
    {
        return Lines::of($this->lines()->filter($filter));
    }

    public function transform(callable $map): Sequence
    {
        return $this->lines()->map($map);
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->lines()->reduce($carry, $reducer);
    }

    public function toString(): string
    {
        $lines = $this
            ->sequence()
            ->map(static fn($line) => $line->toString());

        return Str::of('')->join($lines)->toString();
    }

    /**
     * This should be used only for reading chunk by chunk to persist the file
     * to the filesystem
     *
     * The stream returned MUST never be closed
     *
     * @internal
     */
    public function stream(): Readable
    {
        /** @psalm-suppress ImpureFunctionCall */
        return ($this->load)();
    }

    /**
     * @return Sequence<Line>
     */
    private function lines(): Sequence
    {
        return $this
            ->sequence()
            ->map(static fn($line) => Line::fromStream($line));
    }

    /**
     * @return Sequence<Str>
     */
    private function sequence(): Sequence
    {
        return Sequence::lazy(function($cleanup) {
            $stream = $this->stream();
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
                yield $stream
                    ->readLine()
                    ->match(
                        static fn($line) => $line,
                        static fn() => Str::of(''),
                    );
            }

            $rewind();
        });
    }
}
