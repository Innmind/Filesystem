<?php
declare(strict_types = 1);

use Innmind\Filesystem\File\Content as Model;
use Properties\Innmind\Filesystem\Content;
use Innmind\BlackBox\Set;
use Innmind\IO\IO;
use Innmind\Stream\Streams;
use Innmind\Url\Path;

return static function() {
    $capabilities = Streams::fromAmbientAuthority();
    $implementations = [
        [
            'Content::ofString()',
            Set\Sequence::of(Set\Strings::any())->map(
                static fn($lines) => Model::ofString(\implode("\n", $lines)),
            ),
        ],
        [
            'Content::atPath()',
            Set\Elements::of('LICENSE', 'CHANGELOG.md', 'composer.json')
                ->map(Path::of(...))
                ->map(static fn($path) => Model::atPath(
                    $capabilities->readable(),
                    IO::of($capabilities->watch()->waitForever(...))->readable(),
                    $path,
                )),
        ],
        [
            'Content::none()',
            Set\Elements::of(Model::none()),
        ],
    ];

    foreach ($implementations as [$name, $content]) {
        yield properties(
            $name,
            Content::properties(),
            $content,
        );

        foreach (Content::all() as $property) {
            yield property(
                $property,
                $content,
            )->named($name);
        }
    }
};
