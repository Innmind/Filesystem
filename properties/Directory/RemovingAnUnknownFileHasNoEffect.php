<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Name,
    Directory,
};
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
            $this->toArray($directory),
            $this->toArray($directory->remove($this->name)),
        );

        return $directory;
    }

    private function toArray(Directory $directory): array
    {
        return $directory->reduce(
            [],
            static function($all, $file) {
                $all[] = $file;

                return $all;
            },
        );
    }
}
