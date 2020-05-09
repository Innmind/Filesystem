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
            static fn(string $name): Model => new Model($name),
            Set\Composite::immutable(
                static fn(string $first, array $chrs): string => $first.\implode('', $chrs),
                Set\Decorate::immutable(
                    static fn(int $chr): string => \chr($chr),
                    new Set\Either(
                        Set\Elements::of(33, 126, 127),
                        Set\Integers::between(1, 8),
                        Set\Integers::between(14, 31),
                        Set\Integers::between(35, 38),
                        Set\Integers::between(40, 46),
                        Set\Integers::between(48, 122),
                        Set\Integers::between(48, 122),
                    ),
                ),
                Set\Sequence::of(
                    Set\Decorate::immutable(
                        static fn(int $chr): string => \chr($chr),
                        new Set\Either(
                            Set\Integers::between(1, 33),
                            Set\Integers::between(35, 38),
                            Set\Integers::between(40, 46),
                            Set\Integers::between(48, 127),
                        ),
                    ),
                    Set\Integers::between(0, 254),
                ),
            )->filter(static fn(string $name): bool => $name !== '.' && $name !== '..'),
        );
    }
}
