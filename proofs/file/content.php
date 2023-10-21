<?php
declare(strict_types = 1);

use Innmind\Filesystem\File\{
    Content as Model,
    Content\Line,
};
use Properties\Innmind\Filesystem\Content;
use Innmind\BlackBox\Set;
use Innmind\IO\IO;
use Innmind\Stream\Streams;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Sequence,
};

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
        [
            'Content::ofLines()',
            Set\Sequence::of(
                Set\Strings::madeOf(
                    Set\Unicode::any()->filter(static fn($char) => $char !== "\n"),
                )
                    ->map(Str::of(...))
                    ->map(Line::of(...)),
            )
                ->map(static fn($lines) => Model::ofLines(Sequence::of(...$lines))),
        ],
        [
            'Content::ofChunks()',
            Set\Sequence::of(
                Set\Strings::madeOf(Set\Unicode::any())->map(Str::of(...)),
            )
                ->map(static fn($chunks) => Model::ofChunks(Sequence::of(...$chunks))),
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
