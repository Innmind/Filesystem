<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\FileNotFound;
use Innmind\Immutable\Set;

interface Directory extends File
{
    public function add(File $file): self;

    /**
     * @throws FileNotFound
     */
    public function get(Name $name): File;
    public function contains(Name $name): bool;
    public function remove(Name $name): self;

    /**
     * @param callable(File): void $function
     */
    public function foreach(callable $function): void;

    /**
     * @param callable(File): bool $predicate
     *
     * @return Set<File>
     */
    public function filter(callable $predicate): Set;

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
