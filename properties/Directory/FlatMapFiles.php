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
        $directory2 = $directory->flatMap(fn($file) => Directory::of(
            Name::of('doesntmatter'),
            Set::of(
                $this->file1->rename(Name::of($this->file1->name()->toString().$file->name()->toString())),
                $this->file2->rename(Name::of($this->file2->name()->toString().$file->name()->toString())),
            ),
        ));

        Assert::assertNotSame($directory, $directory2);
        Assert::assertSame($directory->name()->toString(), $directory2->name()->toString());
        Assert::assertNotSame($directory->files(), $directory2->files());
        Assert::assertSame($directory->files()->size() * 2, $directory2->files()->size());
        Assert::assertSame(
            [$this->file1->content(), $this->file2->content()],
            $directory2
                ->files()
                ->map(static fn($file) => $file->content())
                ->distinct()
                ->toList(),
        );

        return $directory2;
    }
}
