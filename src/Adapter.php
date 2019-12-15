<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\FileNotFound;
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
    public function get(Name $file): File;
    public function contains(Name $file): bool;
    public function remove(Name $file): void;

    /**
     * @return Set<File>
     */
    public function all(): Set;
}
