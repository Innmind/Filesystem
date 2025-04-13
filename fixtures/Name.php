<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\Name as Model;
use Innmind\BlackBox\Set;

final class Name
{
    /**
     * @return Set<Model>
     */
    public static function any(): Set
    {
        return self::strings()->map(Model::of(...));
    }

    /**
     * @return Set<string>
     */
    public static function strings(): Set
    {
        return Set::strings()->madeOf(
            Set::integers()->between(32, 46)->map(\chr(...)),
            Set::integers()->between(48, 126)->map(\chr(...)),
            Set::strings()->unicode()->emoticons(),
        )
            ->between(1, 255)
            ->filter(static fn($name) => \mb_strlen($name, 'ASCII') <= 255)
            ->filter(
                static fn(string $name): bool => $name !== '.' &&
                    $name !== '..' &&
                    !\preg_match('~\s+~', $name),
            );
    }
}
