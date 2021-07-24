<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File\Content,
    Exception\CannotPersistClosedStream,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @internal
 */
final class Chunk
{
    /**
     * @return Sequence<Str>
     */
    public function __invoke(Content $content): Sequence
    {
        $stream = $content->stream();

        if ($stream->closed()) {
            throw new CannotPersistClosedStream;
        }

        $stream->rewind();

        return Sequence::lazy(static function() use ($stream) {
            while (!$stream->end()) {
                yield $stream->read(8192);
            }

            // Closing the stream is safe as the Content should return a new
            // stream each time so there should ne no side effect
            $stream->close();
        });
    }
}
