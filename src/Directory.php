<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\{
    Set,
    Maybe,
};

interface Directory extends File
{
    public function add(File $file): self;

    /**
     * @return Maybe<File>
     */
    public function get(Name $name): Maybe;
    public function contains(Name $name): bool;
    public function remove(Name $name): self;

    /**
     * @param callable(File): void $function
     */
    public function foreach(callable $function): void;

    /**
     * @param callable(File): bool $predicate
     */
    public function filter(callable $predicate): self;

    /**
     * @template R
     *
     * @param R $carry
     * @param callable(R, File): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer);

    /**
     * @return Set<Name>
     */
    public function removed(): Set;
}
