<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory\Directory,
    Name,
    Event\FileWasAdded,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddDirectory implements Property
{
    private const NAME = 'Some new directory';

    public function name(): string
    {
        return 'Add directory';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains(new Name(self::NAME));
    }

    public function ensureHeldBy(object $directory): object
    {
        $file = new Directory(
            new Name(self::NAME),
        );

        Assert::assertFalse($directory->contains($file->name()));
        $newDirectory = $directory->add($file);
        Assert::assertNotSame($directory, $newDirectory);
        Assert::assertFalse($directory->contains($file->name()));
        Assert::assertTrue($newDirectory->contains($file->name()));
        Assert::assertGreaterThan(
            $directory->modifications()->size(),
            $newDirectory->modifications()->size(),
        );
        Assert::assertInstanceOf(
            FileWasAdded::class,
            $newDirectory->modifications()->last(),
        );
        Assert::assertSame(
            $file,
            $newDirectory->modifications()->last()->file(),
        );

        return $newDirectory;
    }
}
