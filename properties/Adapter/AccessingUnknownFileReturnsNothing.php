<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\Name;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AccessingUnknownFileReturnsNothing implements Property
{
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return "Accessing unknown file '{$this->name->toString()}' must throw an exception";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->name);
    }

    public function ensureHeldBy(object $adapter): object
    {
        Assert::assertNull($adapter->get($this->name)->match(
            static fn($file) => $file,
            static fn() => null,
        ));

        return $adapter;
    }
}
