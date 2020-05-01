<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File\File,
    Name,
    Stream\NullStream,
    Event\FileWasAdded,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddFile implements Property
{
    private const NAME = 'Some new file';

    public function name(): string
    {
        return 'Add file';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains(new Name(self::NAME));
    }

    public function ensureHeldBy(object $directory): object
    {
        $file = new File(
            new Name(self::NAME),
            new NullStream,
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
