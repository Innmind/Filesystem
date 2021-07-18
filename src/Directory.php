<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\{
    Set,
    Maybe,
    SideEffect,
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
    public function foreach(callable $function): SideEffect;

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
     * This method should only be used for implementations of the Adapter
     * interface, normal users should never have to use this method
     *
     * @return Set<Name>
     */
    public function removed(): Set;
}
