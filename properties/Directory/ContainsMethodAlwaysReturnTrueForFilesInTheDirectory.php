<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ContainsMethodAlwaysReturnTrueForFilesInTheDirectory implements Property
{
    public function name(): string
    {
        return 'contains() always return true for file in the directory';
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        $directory->foreach(static function($file) use ($directory) {
            Assert::assertTrue($directory->contains($file->name()));
        });

        return $directory;
    }
}
