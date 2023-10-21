<?php
declare(strict_types = 1);

use Innmind\Filesystem\File\Content as Model;
use Properties\Innmind\Filesystem\Content;
use Innmind\BlackBox\Set;

return static function() {
    $ofString = Set\Sequence::of(Set\Strings::any())->map(
        static fn($lines) => Model::ofString(\implode("\n", $lines)),
    );

    yield properties(
        'Content::ofString()',
        Content::properties(),
        $ofString,
    );

    foreach (Content::all() as $property) {
        yield property(
            $property,
            $ofString,
        )->named('Content::ofString()');
    }
};
