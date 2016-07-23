<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\MapInterface;

/**
 * Layer between value objects and concrete implementation
 */
interface AdapterInterface
{
    /**
     * Persist the file to concrete filesystem
     *
     * @param FileInterface $file
     *
     * @return self
     */
    public function add(FileInterface $file): self;

    /**
     * Return the file with the given name
     *
     * @param string $file
     *
     * @throws FileNotFoundException
     *
     * @return FileInterface
     */
    public function get(string $file): FileInterface;

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
     * @return MapInterface<string, FileInterface>
     */
    public function all(): MapInterface;
}
