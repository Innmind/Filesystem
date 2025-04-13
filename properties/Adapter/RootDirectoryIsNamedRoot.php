<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\Adapter;
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Adapter>
 */
final class RootDirectoryIsNamedRoot implements Property
{
    public static function any(): Set
    {
        return Set::of(new self);
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $assert->same('root', $adapter->root()->name()->toString());

        return $adapter;
    }
}
