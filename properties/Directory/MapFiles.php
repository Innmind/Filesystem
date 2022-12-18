<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\File;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class MapFiles implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function name(): string
    {
        return 'Map files';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->files()->empty();
    }

    public function ensureHeldBy(object $directory): object
    {
        $directory2 = $directory->map(fn($file) => $this->file->rename($file->name()));

        Assert::assertNotSame($directory, $directory2);
        Assert::assertSame($directory->name()->toString(), $directory2->name()->toString());
        Assert::assertNotSame($directory->files(), $directory2->files());
        Assert::assertSame($directory->files()->size(), $directory2->files()->size());
        Assert::assertSame(
            [$this->file->content()],
            $directory2
                ->files()
                ->map(static fn($file) => $file->content())
                ->distinct()
                ->toList(),
        );
        Assert::assertSame($directory->removed(), $directory2->removed());

        return $directory2;
    }
}
