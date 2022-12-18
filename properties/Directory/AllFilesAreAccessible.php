<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AllFilesAreAccessible implements Property
{
    public function name(): string
    {
        return 'All files are accessible';
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        Assert::assertSame(
            $directory->reduce(
                [],
                static fn($all, $file) => \array_merge($all, [$file]),
            ),
            $directory->files()->toList(),
        );

        return $directory;
    }
}
