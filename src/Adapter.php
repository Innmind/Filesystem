<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\Set;

/**
 * Layer between value objects and concrete implementation
 */
interface Adapter
{
    public function add(File $file): void;

    /**
     * @throws FileNotFound
     */
    public function get(string $file): File;
    public function contains(string $file): bool;
    public function remove(string $file): void;

    /**
     * @return Set<File>
     */
    public function all(): Set;
}
