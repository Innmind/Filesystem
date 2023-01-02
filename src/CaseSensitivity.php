<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\Set;

enum CaseSensitivity
{
    case sensitive;
    case insensitive;

    /**
     * @internal
     *
     * @param Set<Name> $in
     */
    public function contains(Name $name, Set $in): bool
    {
        return match ($this) {
            self::sensitive => $in
                ->map($this->normalize(...))
                ->contains($this->normalize($name)),
            self::insensitive => $in
                ->map($this->normalize(...))
                ->contains($this->normalize($name)),
        };
    }

    private function normalize(Name $name): string
    {
        return match ($this) {
            self::sensitive => $name->toString(),
            self::insensitive => $name->str()->toLower()->toString(),
        };
    }
}
