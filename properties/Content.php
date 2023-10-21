<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem;

use Innmind\BlackBox\Set;

final class Content
{
    public static function properties(): Set
    {
        return Set\Properties::any(
            ...\array_map(
                static fn($class) => $class::any(),
                self::all(),
            ),
        );
    }

    public static function all(): array
    {
        return [
            Content\ForeachExposeAllLines::class,
            Content\ForeachExposeAtLeastOneLine::class,
            Content\Map::class,
            Content\FlatMap::class,
            Content\Filter::class,
            Content\Lines::class,
            Content\Chunks::class,
            Content\Reduce::class,
            Content\Size::class,
        ];
    }
}
