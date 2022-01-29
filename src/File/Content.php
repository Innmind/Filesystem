<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\File\Content\Line;
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
     * @param callable(Line): self $map
     */
    public function flatMap(callable $map): self;

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
}
