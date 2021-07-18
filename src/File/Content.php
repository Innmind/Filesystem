<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\File\Content\Line;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
};

/**
 * @psalm-immutable
 */
interface Content
{
    /**
     * @param callable(Line): void $function
     */
    public function foreach(callable $function): SideEffect;

    /**
     * @param callable(Line): Line $map
     */
    public function map(callable $map): self;

    /**
     * @param callable(Line): bool $filter
     */
    public function filter(callable $filter): self;

    /**
     * @template T
     *
     * @param callable(Line): T $map
     *
     * @return Sequence<T>
     */
    public function transform(callable $map): Sequence;

    /**
     * @template T
     *
     * @param T $carry
     * @param callable(T, Line): T $reducer
     *
     * @return T
     */
    public function reduce($carry, callable $reducer);
    public function toString(): string;

    /**
     * This method should be used with extreme care as manipulating a stream
     * manually can lead to unexpected behaviour in your code.
     *
     * Implementations of this interface should always return a different
     * instance of the stream object to avoid side effects in the implementation
     */
    public function stream(): Readable;
}
