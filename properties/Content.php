<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem;

use Innmind\Filesystem\File\Content as Model;
use Innmind\BlackBox\{
    Set,
    Property,
    Properties,
};

final class Content
{
    /**
     * @return Set<Properties>
     */
    public static function properties(): Set
    {
        return Set\Properties::any(
            ...\array_map(
                static fn($class) => $class::any(),
                self::all(),
            ),
        );
    }

    /**
     * @return class-string<Property<Model>>
     */
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
