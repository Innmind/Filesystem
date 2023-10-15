<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    Directory,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};

/**
 * @implements Property<Adapter>
 */
final class ReAddingFilesHasNoSideEffect implements Property
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
            ->foreach(static function($file) use ($assert, $adapter) {
                $adapter->add($file);
                $assert->true($adapter->contains($file->name()));

                if ($file instanceof Directory) {
                    return;
                }

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
