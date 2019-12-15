<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
};
use Innmind\Immutable\{
    Map,
    Set,
};

final class CacheOpenedFiles implements Adapter
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
    public function get(Name $file): File
    {
        if ($this->files->contains($file->toString())) {
            return $this->files->get($file->toString());
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
    public function contains(Name $file): bool
    {
        if ($this->files->contains($file->toString())) {
            return true;
        }

        return $this->filesystem->contains($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $file): void
    {
        $this->files = $this->files->remove($file->toString());
        $this->filesystem->remove($file);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Set
    {
        $all = $this->filesystem->all();
        $this->files = $all->toMapOf(
            'string',
            File::class,
            static fn(File $file): \Generator => yield $file->name()->toString() => $file,
        );

        return $all;
    }
}
