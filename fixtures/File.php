<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\File\File as Model;
use Innmind\BlackBox\Set;
use Innmind\Stream\Readable\Stream;
use Fixtures\Innmind\MediaType\MediaType;

final class File
{
    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn($name, $content, $mediaType): Model => new Model(
                $name,
                Stream::ofContent($content),
                $mediaType,
            ),
            Name::any(),
            Set\Strings::any(),
            MediaType::any(),
        );
    }
}
