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
                    Set\Elements::of(
                        33,
                        ...range(1, 8),
                        ...range(14, 31),
                        ...range(35, 38),
                        ...range(40, 46),
                        ...range(48, 122),
                        ...range(126, 127),
                    ),
                ),
                Set\Sequence::of(
                    Set\Decorate::immutable(
                        static fn(int $chr): string => \chr($chr),
                        Set\Elements::of(
                            ...range(1, 33),
                            ...range(35, 38),
                            ...range(40, 46),
                            ...range(48, 127),
                        ),
                    ),
                    Set\Integers::between(0, 254),
                ),
            )->filter(static fn(string $name): bool => $name !== '.' && $name !== '..'),
        );
    }
}
