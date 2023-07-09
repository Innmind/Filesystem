<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    Name,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\Name as FName;

/**
 * @implements Property<Adapter>
 */
final class RemoveUnknownFile implements Property
{
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public static function any(): Set
    {
        return FName::any()->map(static fn($name) => new self($name));
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $assert->null($adapter->remove($this->name));
        $assert->false($adapter->contains($this->name));

        return $adapter;
    }
}
