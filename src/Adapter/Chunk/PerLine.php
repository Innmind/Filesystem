<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter\Chunk;

use Innmind\Filesystem\File\{
    Content,
    Content\Line,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @internal
 */
final class PerLine
{
    /**
     * @return Sequence<Str>
     */
    public function __invoke(Content $content): Sequence
    {
        $firstLineRead = false;

        return $content->transform(static function(Line $line) use (&$firstLineRead) {
            if (!$firstLineRead) {
                $firstLineRead = true;

                return Str::of($line->toString());
            }

            return Str::of("\n".$line->toString());
        });
    }
}
