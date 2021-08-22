<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\{
    File\Content,
    Stream\LazyStream,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
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
        $stream = ($this->load)();
        /** @psalm-suppress ImpureMethodCall */
        $stream->rewind();
        /** @psalm-suppress ImpureMethodCall */
        $content = $stream->toString();
        /** @psalm-suppress ImpureMethodCall */
        $stream->rewind();

        return $content;
    }

    public function stream(): Readable
    {
        return ($this->load)();
    }

    /**
     * @return Sequence<Line>
     */
    private function sequence(): Sequence
    {
        return Sequence::lazy(function() {
            $stream = $this->stream();
            $stream->rewind();

            while (!$stream->end()) {
                $line = $stream->readLine();

                yield Line::fromStream($line);
            }

            $stream->rewind();
        });
    }
}
