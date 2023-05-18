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
    private Directory $root;

    private function __construct()
    {
        $this->root = Directory\Directory::named('root');
    }

    public static function new(): self
    {
        return new self;
    }

    public function add(File $file): void
    {
        $this->root = $this->root->add($file);
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

    public function all(): Set
    {
        return $this->root()->files()->toSet();
    }

    public function root(): Directory
    {
        return $this->root;
    }
}
