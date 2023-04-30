<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
interface Chunkable
{
    /**
     * @return Sequence<Str>
     */
    public function chunks(): Sequence;
}
