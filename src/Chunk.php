<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\{
    Sequence,
    Str,
};

final class Chunk
{
    /**
     * @return Sequence<Str>
     */
    public function __invoke(File\Content $content): Sequence
    {
        if ($content instanceof File\Content\Chunkable) {
            return $content->chunks();
        }

        $firstLineRead = false;

        return $content->lines()->map(static function($line) use (&$firstLineRead) {
            if (!$firstLineRead) {
                $firstLineRead = true;

                return Str::of($line->toString());
            }

            return Str::of("\n".$line->toString());
        });
    }
}
