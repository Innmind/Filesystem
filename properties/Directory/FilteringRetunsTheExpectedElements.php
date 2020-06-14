<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\File;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class FilteringRetunsTheExpectedElements implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function name(): string
    {
        return 'Filtering returns the expected elements';
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        $shouldBeEmpty = $directory->filter(fn($file): bool => $file === $this->file);
        $shouldContainsOurFile = $directory
            ->add($this->file)
            ->filter(fn($file): bool => $file->name() === $this->file->name());

        Assert::assertCount(0, $shouldBeEmpty);
        Assert::assertCount(1, $shouldContainsOurFile);
        Assert::assertTrue($shouldContainsOurFile->contains($this->file));

        return $directory;
    }
}
