<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
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
 * @implements Property<Adapter>
 */
final class AddEmptyDirectory implements Property
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

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->name);
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $directory = Directory::of($this->name);

        $assert->false($adapter->contains($directory->name()));
        $assert->null($adapter->add($directory));
        $assert->true($adapter->contains($directory->name()));
        $assert->same(
            [],
            $adapter->get($directory->name())->match(
                static fn($dir) => $dir->reduce(
                    [],
                    static function(array $files, $file): array {
                        $files[] = $file;

                        return $files;
                    },
                ),
                static fn() => null,
            ),
        );

        return $adapter;
    }
}
