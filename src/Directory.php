<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\Sequence;

interface Directory extends File
{
    public function add(File $file): self;

    /**
     * @throws FileNotFound
     */
    public function get(string $name): File;
    public function contains(string $name): bool;
    public function remove(string $name): self;
    public function replaceAt(string $path, File $file): self;

    /**
     * @param callable(File): void $function
     */
    public function foreach(callable $function): void;

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
     * @return Sequence<object>
     */
    public function modifications(): Sequence;
}
