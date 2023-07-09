<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Name,
    Directory,
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
final class RemovingAnUnknownFileHasNoEffect implements Property
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
        $assert->same(
            $this->toArray($directory),
            $this->toArray($directory->remove($this->name)),
        );

        return $directory;
    }

    private function toArray(Directory $directory): array
    {
        return $directory->reduce(
            [],
            static function($all, $file) {
                $all[] = $file;

                return $all;
            },
        );
    }
}
