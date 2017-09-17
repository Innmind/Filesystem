<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\MapInterface;

/**
 * Layer between value objects and concrete implementation
 */
interface Adapter
{
    public function add(File $file): self;

    /**
     * @throws FileNotFound
     */
    public function get(string $file): File;
    public function has(string $file): bool;

    /**
     * @throws FileNotFound
     */
    public function remove(string $file): self;

    /**
     * @return MapInterface<string, File>
     */
    public function all(): MapInterface;
}
