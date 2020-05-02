<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\Name;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemoveUnknownFile implements Property
{
    private const NAME = 'Unknown file';

    public function name(): string
    {
        return 'Remove unknown file';
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(object $adapter): object
    {
        Assert::assertNull($adapter->remove(new Name(self::NAME)));
        Assert::assertFalse($adapter->contains(new Name(self::NAME)));

        return $adapter;
    }
}
