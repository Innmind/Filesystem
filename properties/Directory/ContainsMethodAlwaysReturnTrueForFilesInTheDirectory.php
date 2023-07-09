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
final class ContainsMethodAlwaysReturnTrueForFilesInTheDirectory implements Property
{
    public static function any(): Set
    {
        return Set\Elements::of(new self);
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        $directory->foreach(static function($file) use ($assert, $directory) {
            $assert->true($directory->contains($file->name()));
        });

        return $directory;
    }
}
