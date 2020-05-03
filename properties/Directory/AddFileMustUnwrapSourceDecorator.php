<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Source,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddFileMustUnwrapSourceDecorator implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function name(): string
    {
        return "Add file '{$this->file->name()->toString()}' must unwrap source decorator";
    }

    public function applicableTo(object $directory): bool
    {
        return $directory instanceof Source;
    }

    public function ensureHeldBy(object $directory): object
    {
        $newDirectory = $directory->add($this->file);
        Assert::assertNotInstanceOf(Source::class, $newDirectory);

        return $newDirectory;
    }
}
