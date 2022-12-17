<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Directory\Directory,
    Name,
};
use Innmind\Immutable\Set;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class FlatMapFiles implements Property
{
    private File $file1;
    private File $file2;

    public function __construct(File $file1, File $file2)
    {
        $this->file1 = $file1;
        $this->file2 = $file2;
    }

    public function name(): string
    {
        return 'FlatMap files';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->files()->empty();
    }

    public function ensureHeldBy(object $directory): object
    {
        $directory2 = $directory->flatMap(fn() => Directory::of(
            Name::of('doesntmatter'),
            Set::of($this->file1, $this->file2),
        ));

        Assert::assertNotSame($directory, $directory2);
        Assert::assertSame($directory->name()->toString(), $directory2->name()->toString());
        Assert::assertNotSame([$this->file1, $this->file2], $directory->files()->toList());
        Assert::assertSame([$this->file1, $this->file2], $directory2->files()->toList());
        Assert::assertSame($directory->removed(), $directory2->removed());

        return $directory2;
    }
}
