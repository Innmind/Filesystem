<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File\Content,
    Chunk as PublicChunk,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @deprecated
 * @internal
 */
final class Chunk
{
    /**
     * @return Sequence<Str>
     */
    public function __invoke(Content $content): Sequence
    {
        return (new PublicChunk)($content);
    }
}
