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
            Set\Strings::any()
                ->filter(static fn($s) => \strpos($s, '/') === false)
                ->filter(static fn($s) => $s !== ''),
        );
    }
}
