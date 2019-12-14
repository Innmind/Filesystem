<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Exception\FileNotFound,
};
use Innmind\Immutable\{
    Map,
    Set,
};

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
    public function add(File $file): void
    {
        $this->files = ($this->files)(
            $file->name()->toString(),
            $file,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $file): File
    {
        if (!$this->contains($file)) {
            throw new FileNotFound($file);
        }

        return $this->files->get($file);
    }

    /**
     * {@inheritdoc}
     */
    public function contains(string $file): bool
    {
        return $this->files->contains($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $file): void
    {
        $this->files = $this->files->remove($file);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Set
    {
        return $this->files->values()->toSetOf(File::class);
    }
}
