<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\File\{
    File as Model,
    Content,
};
use Innmind\BlackBox\Set;
use Fixtures\Innmind\MediaType\MediaType;

final class File
{
    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn($name, $content, $mediaType) => Model::of(
                $name,
                Content::ofString($content),
                $mediaType,
            ),
            Name::any(),
            Set\Strings::any(),
            MediaType::any(),
        );
    }
}
