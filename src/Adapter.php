<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\MapInterface;

/**
 * Layer between value objects and concrete implementation
 */
interface Adapter
{
    /**
     * Persist the file to concrete filesystem
     *
     * @param File $file
     *
     * @return self
     */
    public function add(File $file): self;

    /**
     * Return the file with the given name
     *
     * @param string $file
     *
     * @throws FileNotFoundException
     *
     * @return File
     */
    public function get(string $file): File;

    /**
     * Check if the file exists
     *
     * @param string $file
     *
     * @return bool
     */
    public function has(string $file): bool;

    /**
     * Remove the file with the given name
     *
     * @param string $file
     *
     * @throws FileNotFoundException
     *
     * @return self
     */
    public function remove(string $file): self;

    /**
     * @return MapInterface<string, File>
     */
    public function all(): MapInterface;
}
