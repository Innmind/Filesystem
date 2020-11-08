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
    /** @var Map<string, File> */
    private Map $files;
    private Adapter $filesystem;

    public function __construct(Adapter $filesystem)
    {
        $this->filesystem = $filesystem;
        /** @var Map<string, File> */
        $this->files = Map::of('string', File::class);
    }

    public function add(File $file): void
    {
        $this->filesystem->add($file);
        $this->files = ($this->files)(
            $file->name()->toString(),
            $file,
        );
    }

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

    public function contains(Name $file): bool
    {
        if ($this->files->contains($file->toString())) {
            return true;
        }

        return $this->filesystem->contains($file);
    }

    public function remove(Name $file): void
    {
        $this->files = $this->files->remove($file->toString());
        $this->filesystem->remove($file);
    }

    public function all(): Set
    {
        $all = $this->filesystem->all();
        /** @var Map<string, File> */
        $this->files = $all->toMapOf(
            'string',
            File::class,
            static fn(File $file): \Generator => yield $file->name()->toString() => $file,
        );

        return $all;
    }
}
