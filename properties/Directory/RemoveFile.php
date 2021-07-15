<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemoveFile implements Property
{
    public function name(): string
    {
        return 'Remove file';
    }

    public function applicableTo(object $directory): bool
    {
        // at least one file must exist
        return $directory->reduce(
            false,
            static fn() => true,
        );
    }

    public function ensureHeldBy(object $directory): object
    {
        $file = $directory->reduce(
            null,
            static fn($found, $file) => $found ?? $file,
        );

        $newDirectory = $directory->remove($file->name());
        Assert::assertFalse($newDirectory->contains($file->name()));
        Assert::assertTrue($directory->contains($file->name()));
        Assert::assertGreaterThan(
            $directory->removed()->size(),
            $newDirectory->removed()->size(),
        );
        Assert::assertSame(
            $file->name(),
            $newDirectory->removed()->last(),
        );

        return $newDirectory;
    }
}
