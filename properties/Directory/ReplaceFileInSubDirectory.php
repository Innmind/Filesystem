<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Name,
    Directory\Directory,
    Event\FileWasAdded,
};
use Innmind\Url\Path;
use Innmind\Immutable\Set;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ReplaceFileInSubDirectory implements Property
{
    private Name $level1;
    private Name $level2;
    private File $file;

    public function __construct(Name $level1, Name $level2, File $file)
    {
        $this->level1 = $level1;
        $this->level2 = $level2;
        $this->file = $file;
    }

    public function name(): string
    {
        return 'Replace file in sub directory';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains($this->level1);
    }

    public function ensureHeldBy(object $directory): object
    {
        $directory = $directory->add(new Directory(
            $this->level1,
            Set::of(
                File::class,
                new Directory($this->level2),
            ),
        ));
        $newDirectory = $directory->replaceAt(
            Path::of('/'.$this->level1->toString().'/'.$this->level2->toString().'/'),
            $this->file,
        );

        Assert::assertNotSame($directory, $newDirectory);
        Assert::assertFalse(
            $directory
                ->get($this->level1)
                ->get($this->level2)
                ->contains($this->file->name())
        );
        Assert::assertTrue(
            $newDirectory
                ->get($this->level1)
                ->get($this->level2)
                ->contains($this->file->name())
        );
        Assert::assertInstanceOf(
            FileWasAdded::class,
            $newDirectory->modifications()->last(),
        );
        Assert::assertInstanceOf(
            FileWasAdded::class,
            $newDirectory
                ->get($this->level1)
                ->modifications()
                ->last(),
        );
        Assert::assertInstanceOf(
            FileWasAdded::class,
            $newDirectory
                ->get($this->level1)
                ->get($this->level2)
                ->modifications()
                ->last(),
        );

        return $newDirectory;
    }
}
