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
        $directory2 = $directory->map(fn() => $this->file);

        Assert::assertNotSame($directory, $directory2);
        Assert::assertSame($directory->name()->toString(), $directory2->name()->toString());
        Assert::assertNotSame([$this->file], $directory->files()->toList());
        Assert::assertSame([$this->file], $directory2->files()->toList());
        Assert::assertSame($directory->removed(), $directory2->removed());

        return $directory2;
    }
}
