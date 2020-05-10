<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Name,
    Exception\FileNotFound,
};
use Innmind\Url\Path;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ReplacingFileAtUnknownPathMustThrowAnException implements Property
{
    private Name $unknown;
    private File $file;

    public function __construct(Name $unknown, File $file)
    {
        $this->unknown = $unknown;
        $this->file = $file;
    }

    public function name(): string
    {
        return 'Replacing file at unknown path must throw an exception';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains($this->unknown);
    }

    public function ensureHeldBy(object $directory): object
    {
        try {
            $directory->replaceAt(
                Path::of($this->unknown->toString()),
                $this->file,
            );

            Assert::fail('it should throw');
        } catch (FileNotFound $e) {
            Assert::assertTrue(true);
        }

        return $directory;
    }
}
