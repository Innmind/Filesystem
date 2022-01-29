<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter\Chunk;

use Innmind\Filesystem\{
    File\Content,
    Exception\CannotPersistClosedStream,
    Exception\FailedToLoadFile,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @internal
 */
final class Fixed
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

        /** @var Readable */
        $stream = $stream->rewind()->match(
            static fn($stream) => $stream,
            static fn() => throw new FailedToLoadFile,
        );

        return Sequence::lazy(static function() use ($stream) {
            while (!$stream->end()) {
                yield $stream->read(8192)->match(
                    static fn($chunk) => $chunk,
                    static fn() => throw new FailedToLoadFile,
                );
            }

            // Calling the rewind here helps always leave the streams in a
            // readable state. It also helps avoid a fatal error when handling
            // too many files (see LazyStream::rewind() for more explanations)
            $_ = $stream->rewind()->match(
                static fn($stream) => $stream,
                static fn() => throw new FailedToLoadFile,
            );
        });
    }
}
