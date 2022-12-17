<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Directory,
};
use Innmind\Immutable\{
    Map,
    Set,
    Maybe,
};

final class InMemory implements Adapter
{
    /** @var Map<string, File> */
    private Map $files;

    private function __construct()
    {
        /** @var Map<string, File> */
        $this->files = Map::of();
    }

    public static function new(): self
    {
        return new self;
    }

    public function add(File $file): void
    {
        $this->files = ($this->files)(
            $file->name()->toString(),
            $file,
        );
    }

    public function get(Name $file): Maybe
    {
        return $this->files->get($file->toString());
    }

    public function contains(Name $file): bool
    {
        return $this->files->contains($file->toString());
    }

    public function remove(Name $file): void
    {
        $this->files = $this->files->remove($file->toString());
    }

    public function all(): Set
    {
        return Set::of(...$this->root()->files()->toList());
    }

    public function root(): Directory
    {
        return Directory\Directory::of(
            Name::of('root'),
            $this->files->values(),
        );
    }
}
