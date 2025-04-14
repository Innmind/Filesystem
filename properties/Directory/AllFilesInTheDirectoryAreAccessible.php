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
final class AllFilesInTheDirectoryAreAccessible implements Property
{
    public static function any(): Set
    {
        return Set::of(new self);
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        $directory->foreach(static function($file) use ($assert, $directory) {
            $assert->same(
                $file->name(),
                $directory->get($file->name())->match(
                    static fn($file) => $file->name(),
                    static fn() => null,
                ),
            );
        });

        return $directory;
    }
}
