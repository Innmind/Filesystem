<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory,
    Event\FileWasRemoved
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemoveDirectory implements Property
{
    public function name(): string
    {
        return 'Remove directory';
    }

    public function applicableTo(object $directory): bool
    {
        // at least one directory must exist
        return $directory->reduce(
            false,
            static fn($found, $file) => $found || $file instanceof Directory,
        );
    }

    public function ensureHeldBy(object $directory): object
    {
        $file = $directory->reduce(
            null,
            static function($found, $file) {
                if ($found) {
                    return $found;
                }

                if ($file instanceof Directory) {
                    return $file;
                }
            },
        );

        $newDirectory = $directory->remove($file->name());
        Assert::assertFalse($newDirectory->contains($file->name()));
        Assert::assertTrue($directory->contains($file->name()));
        Assert::assertGreaterThan(
            $directory->modifications()->size(),
            $newDirectory->modifications()->size(),
        );
        Assert::assertInstanceOf(
            FileWasRemoved::class,
            $newDirectory->modifications()->last(),
        );
        Assert::assertSame(
            $file->name(),
            $newDirectory->modifications()->last()->file(),
        );

        return $newDirectory;
    }
}
