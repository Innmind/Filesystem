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
final class AllRootFilesAreAccessible implements Property
{
    public static function any(): Set
    {
        return Set\Elements::of(new self);
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $adapter
            ->root()
            ->files()
            ->foreach(static function($file) use ($assert, $adapter) {
                $assert->true($adapter->contains($file->name()));
                $assert->same(
                    $file->content()->toString(),
                    $adapter
                        ->get($file->name())
                        ->map(static fn($file) => $file->content())
                        ->match(
                            static fn($content) => $content->toString(),
                            static fn() => null,
                        ),
                );
            });

        return $adapter;
    }
}
