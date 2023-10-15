<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    Adapter\InMemory\Overwrite,
    Adapter\InMemory\Merge,
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
    private Directory $root;
    private Overwrite|Merge $behaviour;

    private function __construct(Overwrite|Merge $behaviour)
    {
        $this->root = Directory::named('root');
        $this->behaviour = $behaviour;
    }

    public static function new(): self
    {
        return new self(new Overwrite);
    }

    public static function emulateFilesystem(): self
    {
        return new self(new Merge);
    }

    public function add(File|Directory $file): void
    {
        $this->root = ($this->behaviour)($this->root, $file);
    }

    public function get(Name $file): Maybe
    {
        return $this->root->get($file);
    }

    public function contains(Name $file): bool
    {
        return $this->root->contains($file);
    }

    public function remove(Name $file): void
    {
        $this->root = $this->root->remove($file);
    }

    public function root(): Directory
    {
        return $this->root;
    }
}
