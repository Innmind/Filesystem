<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Exception\FileNotFound
};
use Innmind\Immutable\Map;

class MemoryAdapter implements Adapter
{
    private Map $files;

    public function __construct()
    {
        $this->files = Map::of('string', File::class);
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): Adapter
    {
        $this->files = $this->files->put(
            (string) $file->name(),
            $file
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $file): File
    {
        if (!$this->has($file)) {
            throw new FileNotFound($file);
        }

        return $this->files->get($file);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $file): bool
    {
        return $this->files->contains($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $file): Adapter
    {
        if (!$this->has($file)) {
            throw new FileNotFound($file);
        }

        $this->files = $this->files->remove($file);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Map
    {
        return $this->files;
    }
}
