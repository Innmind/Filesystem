<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\{
    File\Content,
    Stream\LazyStream,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class AtPath implements Content
{
    private OfStream $content;

    private function __construct(OfStream $content)
    {
        $this->content = $content;
    }

    /**
     * @psalm-pure
     */
    public static function of(Path $path): self
    {
        return new self(OfStream::lazy(static fn() => new LazyStream($path)));
    }

    public function foreach(callable $function): SideEffect
    {
        return $this->content->foreach($function);
    }

    public function map(callable $map): Content
    {
        return $this->content->map($map);
    }

    public function flatMap(callable $map): Content
    {
        return $this->content->flatMap($map);
    }

    public function filter(callable $filter): Content
    {
        return $this->content->filter($filter);
    }

    public function transform(callable $map): Sequence
    {
        return $this->content->transform($map);
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->content->reduce($carry, $reducer);
    }

    public function size(): Maybe
    {
        return $this->content->size();
    }

    public function toString(): string
    {
        return $this->content->toString();
    }

    /**
     * This should be used only for reading chunk by chunk to persist the file
     * to the filesystem
     *
     * @internal
     */
    public function stream(): Readable
    {
        return $this->content->stream();
    }
}
