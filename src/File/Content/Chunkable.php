<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 *
 * In the next major release this interface will be part of Content
 */
interface Chunkable
{
    /**
     * @return Sequence<Str>
     */
    public function chunks(): Sequence;
}
