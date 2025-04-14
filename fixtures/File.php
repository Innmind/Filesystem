<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\{
    File as Model,
    File\Content,
};
use Innmind\BlackBox\Set;
use Fixtures\Innmind\MediaType\MediaType;

final class File
{
    public static function any(): Set
    {
        return Set::compose(
            static fn($name, $content, $mediaType) => Model::of(
                $name,
                Content::ofString($content),
                $mediaType,
            ),
            Name::any(),
            Set::strings(),
            MediaType::any(),
        )->toSet();
    }
}
