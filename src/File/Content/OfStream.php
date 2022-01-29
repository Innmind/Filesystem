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
        return $this->sequence()->foreach($function);
    }

    public function map(callable $map): Content
    {
        return Lines::of($this->transform($map));
    }

    public function flatMap(callable $map): Content
    {
        return Lines::of($this->sequence())->flatMap($map);
    }

    public function filter(callable $filter): Content
    {
        return Lines::of($this->sequence()->filter($filter));
    }

    public function transform(callable $map): Sequence
    {
        return $this->sequence()->map($map);
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->sequence()->reduce($carry, $reducer);
    }

    public function toString(): string
    {
        /**
         * @psalm-suppress ImpureFunctionCall
         * @psalm-suppress ImpureMethodCall
         * @var Either<PositionNotSeekable, Readable>
         */
        $either = ($this->load)()->rewind();

        return $either
            ->flatMap(fn(Readable $stream) => $this->read($stream))
            ->match(
                static fn($content) => $content,
                static fn() => throw new FailedToLoadFile,
            );
    }

    public function stream(): Readable
    {
        /** @psalm-suppress ImpureFunctionCall */
        return ($this->load)();
    }

    /**
     * @return Sequence<Line>
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
                    ->map(static fn($line) => Line::fromStream($line))
                    ->match(
                        static fn($line) => $line,
                        static fn() => Line::of(Str::of('')),
                    );
            }

            $rewind();
        });
    }

    /**
     * @return Either<null, string>
     */
    private function read(Readable $stream): Either
    {
        /** @psalm-suppress ImpureMethodCall */
        return $stream->toString()->match(
            static fn($content) => $stream
                ->rewind()
                ->map(static fn() => $content)
                ->leftMap(static fn() => null),
            static fn() => Either::left(null),
        );
    }
}
