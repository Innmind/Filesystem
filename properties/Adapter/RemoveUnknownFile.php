<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\Name;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemoveUnknownFile implements Property
{
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return "Remove unknown file '{$this->name->toString()}'";
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(object $adapter): object
    {
        Assert::assertNull($adapter->remove($this->name));
        Assert::assertFalse($adapter->contains($this->name));

        return $adapter;
    }
}
