<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    AdapterInterface,
    FileInterface,
    Exception\FileNotFoundException
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

class MemoryAdapter implements AdapterInterface
{
    private $files;

    public function __construct()
    {
        $this->files = new Map('string', FileInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function add(FileInterface $file): AdapterInterface
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
    public function get(string $file): FileInterface
    {
        if (!$this->has($file)) {
            throw new FileNotFoundException;
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
    public function remove(string $file): AdapterInterface
    {
        if (!$this->has($file)) {
            throw new FileNotFoundException;
        }

        $this->files = $this->files->remove($file);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): MapInterface
    {
        return $this->files;
    }
}
