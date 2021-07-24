<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @internal
 */
final class Chunk
{
    private Chunk\Fixed $fixed;
    private Chunk\PerLine $perLine;

    public function __construct()
    {
        $this->fixed = new Chunk\Fixed;
        $this->perLine = new Chunk\PerLine;
    }

    /**
     * @return Sequence<Str>
     */
    public function __invoke(Content $content): Sequence
    {
        // For files content coming directly from the filesystem we can use the
        // stream to make sure we never read too many data at once as it could
        // cause out of memory errors
        if ($content instanceof Content\AtPath) {
            return ($this->fixed)($content);
        }

        // Reading line per line allows to deal with huge files without loading
        // them completely in memory
        return ($this->perLine)($content);
    }
}
