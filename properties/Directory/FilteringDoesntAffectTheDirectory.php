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
        $all = $directory->filter(static fn(): bool => true);
        $none = $directory->filter(static fn(): bool => false);

        $directory->foreach(static fn($file) => Assert::assertFalse($none->contains($file->name())));
        $directory->foreach(static fn($file) => Assert::assertTrue($all->contains($file->name())));
        $all->foreach(static fn($file) => Assert::assertTrue($directory->contains($file->name())));
        Assert::assertSame($directory->removed(), $all->removed());
        Assert::assertSame($directory->removed(), $none->removed());

        return $directory;
    }
}
