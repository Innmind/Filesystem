<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class FilteringDoesntAffectTheDirectory implements Property
{
    public function name(): string
    {
        return 'Filtering doesn\'t affect the directory';
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        $files = $directory->filter(static fn(): bool => true);
        $set = $directory->filter(static fn(): bool => false);

        Assert::assertTrue($set->empty());
        $files->foreach(static fn($file) => Assert::assertTrue($directory->contains($file->name())));

        return $directory;
    }
}
