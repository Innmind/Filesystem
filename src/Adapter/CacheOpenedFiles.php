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
    Maybe,
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
        $this->files = Map::of();
    }

    public function add(File $file): void
    {
        $this->filesystem->add($file);
        $this->files = ($this->files)(
            $file->name()->toString(),
            $file,
        );
    }

    public function get(Name $file): Maybe
    {
        $load = fn(): Maybe => $this
            ->filesystem
            ->get($file)
            ->map(function($file) {
                $this->files = ($this->files)(
                    $file->name()->toString(),
                    $file,
                );

                return $file;
            });

        return $this
            ->files
            ->get($file->toString())
            ->otherwise($load);
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
        $this->files = Map::of(
            ...$all
                ->map(static fn($file) => [$file->name()->toString(), $file])
                ->toList()
        );

        return $all;
    }
}
