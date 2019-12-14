<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\Map;

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

    /**
     * @throws FileNotFound
     */
    public function remove(string $file): void;

    /**
     * @return Map<string, File>
     */
    public function all(): Map;
}
