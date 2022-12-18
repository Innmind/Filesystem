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
        return Set\Decorate::immutable(
            static fn(array $chrs): string => \implode('', $chrs),
            Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn(int $chr): string => \chr($chr),
                    new Set\Either(
                        Set\Integers::between(1, 46),
                        Set\Integers::between(48, 127),
                    ),
                ),
                Set\Integers::between(1, 255),
            ),
        )->filter(
            static fn(string $name): bool => $name !== '.' &&
                $name !== '..' &&
                !\preg_match('~\s+~', $name),
        );
    }
}
