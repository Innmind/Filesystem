<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\Name;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemovingAnUnknownFileHasNoEffect implements Property
{
    private const UNKNOWN = 'some unknown file name';

    public function name(): string
    {
        return 'Removing an unknown file has no effect';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains(new Name(self::UNKNOWN));
    }

    public function ensureHeldBy(object $directory): object
    {
        Assert::assertSame(
            $directory,
            $directory->remove(new Name(self::UNKNOWN)),
        );

        return $directory;
    }
}
