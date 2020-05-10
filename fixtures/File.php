<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\File\File as Model;
use Innmind\BlackBox\Set;
use Innmind\Stream\{
    Readable\Stream,
    Stream\Position,
};
use Fixtures\Innmind\MediaType\MediaType;

final class File
{
    public static function any(): Set
    {
        return Set\Composite::immutable(
            static function($name, $content, $mediaType, $seek): Model {
                $file = new Model(
                    $name,
                    $stream = Stream::ofContent($content),
                    $mediaType,
                );

                if (\is_int($seek)) {
                    // as the generated seeked position may be higher than the
                    // actual content size
                    $seek = \min(\strlen($content), $seek);

                    $stream->seek(new Position($seek));
                }

                return $file;
            },
            Name::any(),
            Set\Strings::any(),
            MediaType::any(),
            new Set\Either(
                Set\Integers::between(0, 128), // 128 is the max string length by default
                Set\Elements::of(null),
            ),
        );
    }
}
