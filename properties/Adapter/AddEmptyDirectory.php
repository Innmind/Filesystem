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
    private const NAME = 'Some empty directory';

    public function name(): string
    {
        return 'Add empty directory';
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains(new Name(self::NAME));
    }

    public function ensureHeldBy(object $adapter): object
    {
        $directory = new Directory(
            new Name(self::NAME),
        );

        Assert::assertFalse($adapter->contains($directory->name()));
        Assert::assertNull($adapter->add($directory));
        Assert::assertTrue($adapter->contains($directory->name()));
        Assert::assertSame(
            [],
            $adapter
                ->get($directory->name())
                ->reduce(
                    [],
                    static function(array $files, $file): array {
                        $files[] = $file;

                        return $files;
                    },
                ),
        );

        return $adapter;
    }
}
