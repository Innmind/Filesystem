<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\Name;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemovingAnUnknownFileHasNoEffect implements Property
{
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return "Removing unknown file '{$this->name->toString()}' has no effect";
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains($this->name);
    }

    public function ensureHeldBy(object $directory): object
    {
        Assert::assertSame(
            $directory,
            $directory->remove($this->name),
        );

        return $directory;
    }
}
