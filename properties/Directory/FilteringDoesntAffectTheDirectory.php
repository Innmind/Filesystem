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
final class FilteringDoesntAffectTheDirectory implements Property
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
        $all = $directory->filter(static fn(): bool => true);
        $none = $directory->filter(static fn(): bool => false);

        $directory->foreach(static fn($file) => $assert->false($none->contains($file->name())));
        $directory->foreach(static fn($file) => $assert->true($all->contains($file->name())));
        $all->foreach(static fn($file) => $assert->true($directory->contains($file->name())));
        $assert->same($directory->removed(), $all->removed());
        $assert->same($directory->removed(), $none->removed());

        return $directory;
    }
}
