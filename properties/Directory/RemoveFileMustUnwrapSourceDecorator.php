<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Source,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemoveFileMustUnwrapSourceDecorator implements Property
{
    public function name(): string
    {
        return 'Remove file must unwrap source decorator';
    }

    public function applicableTo(object $directory): bool
    {
        // at least one file must exist
        return $directory instanceof Source && $directory->reduce(
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
        Assert::assertNotInstanceOf(Source::class, $newDirectory);

        return $newDirectory;
    }
}
