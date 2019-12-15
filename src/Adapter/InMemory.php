<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Exception\FileNotFound,
};
use Innmind\Immutable\{
    Map,
    Set,
};

final class InMemory implements Adapter
{
    /** @var Map<string, File> */
    private Map $files;

    public function __construct()
    {
        /** @var Map<string, File> */
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
    public function get(Name $file): File
    {
        if (!$this->contains($file)) {
            throw new FileNotFound($file->toString());
        }

        return $this->files->get($file->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function contains(Name $file): bool
    {
        return $this->files->contains($file->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $file): void
    {
        $this->files = $this->files->remove($file->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Set
    {
        return $this->files->values()->toSetOf(File::class);
    }
}
