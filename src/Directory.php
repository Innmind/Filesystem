<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\Sequence;

interface Directory extends File, \Iterator, \Countable
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
     * @return Sequence<object>
     */
    public function modifications(): Sequence;
}
