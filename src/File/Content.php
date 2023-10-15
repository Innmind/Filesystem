<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\File\Content\Line;
use Innmind\Stream\Stream\Size;
use Innmind\Immutable\{
    Str,
    Sequence,
    SideEffect,
    Maybe,
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
     * @param callable(Line): self $map
     */
    public function flatMap(callable $map): self;

    /**
     * @param callable(Line): bool $filter
     */
    public function filter(callable $filter): self;

    /**
     * @return Sequence<Line>
     */
    public function lines(): Sequence;

    /**
     * @return Sequence<Str>
     */
    public function chunks(): Sequence;

    /**
     * @template T
     *
     * @param T $carry
     * @param callable(T, Line): T $reducer
     *
     * @return T
     */
    public function reduce($carry, callable $reducer);

    /**
     * @return Maybe<Size>
     */
    public function size(): Maybe;
    public function toString(): string;
}
