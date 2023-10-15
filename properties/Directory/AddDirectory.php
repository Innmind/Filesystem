<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory,
    Name,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\Name as FName;

/**
 * @implements Property<Directory>
 */
final class AddDirectory implements Property
{
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public static function any(): Set
    {
        return FName::any()->map(static fn($name) => new self($name));
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains($this->name);
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        $file = Directory::of($this->name);

        $assert->false($directory->contains($file->name()));
        $newDirectory = $directory->add($file);
        $assert
            ->expected($directory)
            ->not()
            ->same($newDirectory);
        $assert->false($directory->contains($file->name()));
        $assert->true($newDirectory->contains($file->name()));
        $assert->same(
            $directory->removed()->size(),
            $newDirectory->removed()->size(),
        );

        return $newDirectory;
    }
}
