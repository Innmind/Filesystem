<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Sequence,
    Str,
};

final class None
{
    private function __construct()
    {
        // cannot be instanciated
    }

    /**
     * @psalm-pure
     */
    public static function of(): Content
    {
        return Lines::of(Sequence::of(Line::of(Str::of(''))));
    }
}
