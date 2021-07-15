<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Directory\Directory,
    Name,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddEmptyDirectory implements Property
{
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return "Add empty directory '{$this->name->toString()}'";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->name);
    }

    public function ensureHeldBy(object $adapter): object
    {
        $directory = new Directory(
            $this->name,
        );

        Assert::assertFalse($adapter->contains($directory->name()));
        Assert::assertNull($adapter->add($directory));
        Assert::assertTrue($adapter->contains($directory->name()));
        Assert::assertSame(
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
