<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RootDirectoryIsNamedRoot implements Property
{
    public function name(): string
    {
        return 'Root directory is named root';
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(object $adapter): object
    {
        Assert::assertSame('root', $adapter->root()->name()->toString());

        return $adapter;
    }
}
