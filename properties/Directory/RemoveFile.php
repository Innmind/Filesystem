<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\Directory;
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Directory>
 */
final class RemoveFile implements Property
{
    public static function any(): Set
    {
        return Set::of(new self);
    }

    public function applicableTo(object $directory): bool
    {
        // at least one file must exist
        return $directory->reduce(
            false,
            static fn() => true,
        );
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        $file = $directory->reduce(
            null,
            static fn($found, $file) => $found ?? $file,
        );

        $newDirectory = $directory->remove($file->name());
        $assert->false($newDirectory->contains($file->name()));
        $assert->true($directory->contains($file->name()));
        $assert
            ->number($newDirectory->removed()->size())
            ->greaterThan($directory->removed()->size());
        $assert->true(
            $newDirectory->removed()->contains($file->name()),
        );

        return $newDirectory;
    }
}
