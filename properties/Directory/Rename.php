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
final class Rename implements Property
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
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        $directory2 = $directory->rename($this->name);

        $assert
            ->expected($directory)
            ->not()
            ->same($directory2);
        $assert
            ->expected($this->name)
            ->not()
            ->same($directory->name());
        $assert->same($this->name, $directory2->name());
        $assert->same(
            $directory->files(),
            $directory2->files(),
        );
        $assert->same(
            $directory->removed(),
            $directory2->removed(),
        );

        return $directory2;
    }
}
