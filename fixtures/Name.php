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
        return Set\Decorate::immutable(
            static fn(string $name): Model => Model::of($name),
            self::strings(),
        );
    }

    /**
     * @return Set<string>
     */
    public static function strings(): Set
    {
        return Set\Strings::madeOf(
            Set\Integers::between(32, 46)->map(\chr(...)),
            Set\Integers::between(48, 126)->map(\chr(...)),
            Set\Unicode::emoticons(),
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
