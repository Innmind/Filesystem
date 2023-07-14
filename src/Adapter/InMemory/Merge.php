<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter\InMemory;

use Innmind\Filesystem\{
    File,
    Directory,
};
use Innmind\Immutable\Predicate\Instance;

/**
 * This behaviour emulates a concrete filesystem that merges the directories
 *
 * @internal
 */
final class Merge
{
    public function __invoke(Directory $parent, File $file): Directory
    {
        if (!$file instanceof Directory) {
            return $parent->add($file);
        }

        $file = $parent
            ->get($file->name())
            ->keep(Instance::of(Directory::class))
            ->match(
                fn($existing) => $this->merge($existing, $file),
                static fn() => $file,
            );

        return $parent->add($file);
    }

    private function merge(Directory $existing, Directory $new): Directory
    {
        $existing = $new
            ->removed()
            ->filter(static fn($name) => !$new->contains($name))
            ->reduce(
                $existing,
                static fn(Directory $existing, $name) => $existing->remove($name),
            );

        return $new->reduce(
            $existing,
            fn(Directory $directory, $file) => $this($directory, $file),
        );
    }
}
