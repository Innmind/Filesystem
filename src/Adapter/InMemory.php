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
    Maybe,
    Predicate\Instance,
};

final class InMemory implements Adapter
{
    private Directory $root;

    private function __construct()
    {
        $this->root = Directory::named('root');
    }

    /**
     * @deprecated Use self::emulateFilesystem()
     */
    public static function new(): self
    {
        return self::emulateFilesystem();
    }

    public static function emulateFilesystem(): self
    {
        return new self;
    }

    #[\Override]
    public function add(File|Directory $file): void
    {
        $this->root = $this->merge($this->root, $file);
    }

    #[\Override]
    public function get(Name $file): Maybe
    {
        return $this->root->get($file);
    }

    #[\Override]
    public function contains(Name $file): bool
    {
        return $this->root->contains($file);
    }

    #[\Override]
    public function remove(Name $file): void
    {
        $this->root = $this->root->remove($file);
    }

    #[\Override]
    public function root(): Directory
    {
        return $this->root;
    }

    private function merge(Directory $parent, File|Directory $file): Directory
    {
        if (!$file instanceof Directory) {
            return $parent->add($file);
        }

        $file = $parent
            ->get($file->name())
            ->keep(Instance::of(Directory::class))
            ->match(
                fn($existing) => $this->mergeDirectories($existing, $file),
                static fn() => $file,
            );

        return $parent->add($file);
    }

    private function mergeDirectories(
        Directory $existing,
        Directory $new,
    ): Directory {
        $existing = $new
            ->removed()
            ->filter(static fn($name) => !$new->contains($name))
            ->reduce(
                $existing,
                static fn(Directory $existing, $name) => $existing->remove($name),
            );

        return $new->reduce(
            $existing,
            fn(Directory $directory, $file) => $this->merge($directory, $file),
        );
    }
}
