<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AllFilesInTheDirectoryAreAccessible implements Property
{
    public function name(): string
    {
        return 'All files in the directory are accessible';
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        $directory->foreach(static function($file) use ($directory) {
            Assert::assertSame(
                $file->name(),
                $directory->get($file->name())->name(),
            );
        });

        return $directory;
    }
}
