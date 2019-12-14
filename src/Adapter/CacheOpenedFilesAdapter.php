<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
};
use Innmind\Immutable\Map;

final class CacheOpenedFilesAdapter implements Adapter
{
    private Map $files;
    private Adapter $filesystem;

    public function __construct(Adapter $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->files = Map::of('string', File::class);
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): void
    {
        $this->filesystem->add($file);
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
        if ($this->files->contains($file)) {
            return $this->files->get($file);
        }

        $file = $this->filesystem->get($file);
        $this->files = ($this->files)(
            $file->name()->toString(),
            $file,
        );

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(string $file): bool
    {
        if ($this->files->contains($file)) {
            return true;
        }

        return $this->filesystem->contains($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $file): void
    {
        $this->files = $this->files->remove($file);
        $this->filesystem->remove($file);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Map
    {
        $this->files = $this->filesystem->all();

        return $this->files;
    }
}
