<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\Name;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class Rename implements Property
{
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return "Rename to '{$this->name->toString()}'";
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        $directory2 = $directory->rename($this->name);

        Assert::assertNotSame($directory, $directory2);
        Assert::assertNotSame($this->name, $directory->name());
        Assert::assertSame($this->name, $directory2->name());
        Assert::assertSame(
            $directory->files(),
            $directory2->files(),
        );
        Assert::assertSame(
            $directory->removed(),
            $directory2->removed(),
        );

        return $directory2;
    }
}
