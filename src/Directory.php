<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\DuplicatedFile;
use Innmind\Immutable\{
    Set,
    Sequence,
    Maybe,
    SideEffect,
};

/**
 * @psalm-immutable
 */
interface Directory
{
    public function name(): Name;
    public function rename(Name $name): self;
    public function add(File|self $file): self;

    /**
     * @return Maybe<File|self>
     */
    public function get(Name $name): Maybe;
    public function contains(Name $name): bool;
    public function remove(Name $name): self;

    /**
     * @param callable(File|self): void $function
     */
    public function foreach(callable $function): SideEffect;

    /**
     * @param callable(File|self): bool $predicate
     */
    public function filter(callable $predicate): self;

    /**
     * @param callable(File|self): File $map
     *
     * @throws DuplicatedFile
     */
    public function map(callable $map): self;

    /**
     * @param callable(File|self): self $map
     *
     * @throws DuplicatedFile
     */
    public function flatMap(callable $map): self;

    /**
     * @template R
     *
     * @param R $carry
     * @param callable(R, File|self): R $reducer
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

    /**
     * @return Sequence<File|self>
     */
    public function files(): Sequence;
}
