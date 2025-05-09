<?php
declare(strict_types = 1);
declare(ticks = 1);

use Innmind\Filesystem\File\{
    Content as Model,
    Content\Line,
};
use Properties\Innmind\Filesystem\Content;
use Innmind\BlackBox\Set;
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Sequence,
    Monoid\Concat,
};

return static function() {
    $io = IO::fromAmbientAuthority();

    $implementations = [
        [
            'Content::ofString()',
            Set::sequence(Set::strings())->map(
                static fn($lines) => Model::ofString(\implode("\n", $lines)),
            ),
        ],
        [
            'Content::atPath()',
            Set::of('LICENSE', 'CHANGELOG.md', 'composer.json')
                ->map(Path::of(...))
                ->map(static fn($path) => Model::atPath(
                    $io,
                    $path,
                )),
        ],
        [
            'Content::io()',
            Set::of('LICENSE', 'CHANGELOG.md', 'composer.json')
                ->map(static fn($path) => Model::io(
                    $io->streams()->acquire(
                        \fopen($path, 'r'),
                    ),
                )),
        ],
        [
            'Content::io()',
            Set::of('LICENSE', 'CHANGELOG.md', 'composer.json')
                ->map(static fn($path) => Model::io(
                    $io->files()->read(Path::of($path)),
                )),
        ],
        [
            'Content::none()',
            Set::of(Model::none()),
        ],
        [
            'Content::ofLines()',
            Set::sequence(
                Set::strings()
                    ->madeOf(
                        Set::strings()
                            ->unicode()
                            ->char()
                            ->filter(static fn($char) => $char !== "\n"),
                    )
                    ->map(Str::of(...))
                    ->map(Line::of(...)),
            )
                ->map(static fn($lines) => Model::ofLines(Sequence::of(...$lines))),
        ],
        [
            'Content::ofChunks()',
            Set::sequence(
                Set::strings()
                    ->madeOf(Set::strings()->unicode()->char())
                    ->map(Str::of(...)),
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

    yield test(
        'Content::oneShot()->foreach()',
        static function($assert) use ($io) {
            $content = Model::oneShot($io->streams()->acquire(
                \fopen('LICENSE', 'r'),
            ));

            $count = 0;
            $content->foreach(static function() use (&$count) {
                $count++;
            });

            $assert->same(22, $count);
        },
    );

    yield proof(
        'Content::oneShot()->map()',
        given(
            Set::strings()
                ->madeOf(
                    Set::strings()
                        ->unicode()
                        ->char()
                        ->filter(static fn($char) => $char !== "\n"),
                )
                ->map(Str::of(...))
                ->map(Line::of(...)),
        ),
        static function($assert, $replacement) use ($io) {
            $content = Model::oneShot($io->streams()->acquire(
                \fopen('LICENSE', 'r'),
            ));

            $lines = $content
                ->map(static fn() => $replacement)
                ->lines()
                ->map(static fn($line) => $line->toString())
                ->distinct()
                ->toList();

            $assert->same([$replacement->toString()], $lines);
        },
    );

    yield proof(
        'Content::oneShot()->flatMap()',
        given(
            Set::strings()->madeOf(
                Set::strings()
                    ->unicode()
                    ->char()
                    ->filter(static fn($char) => $char !== "\n"),
            ),
            Set::strings()->madeOf(
                Set::strings()
                    ->unicode()
                    ->char()
                    ->filter(static fn($char) => $char !== "\n"),
            ),
        )->filter(static fn($a, $b) => $a !== $b),
        static function($assert, $replacement1, $replacement2) use ($io) {
            $content = Model::oneShot($io->streams()->acquire(
                \fopen('LICENSE', 'r'),
            ));

            $lines = $content
                ->flatMap(static fn() => Model::ofString($replacement1."\n".$replacement2))
                ->lines()
                ->map(static fn($line) => $line->toString())
                ->distinct()
                ->toList();

            $assert->same([$replacement1, $replacement2], $lines);
        },
    );

    yield test(
        'Content::oneShot()->filter()',
        static function($assert) use ($io) {
            $content = Model::oneShot($io->streams()->acquire(
                \fopen('LICENSE', 'r'),
            ));

            $size = $content
                ->filter(static fn($line) => !$line->str()->empty())
                ->lines()
                ->size();

            $assert->same(17, $size);
        },
    );

    yield test(
        'Content::oneShot()->reduce()',
        static function($assert) use ($io) {
            $content = Model::oneShot($io->streams()->acquire(
                \fopen('LICENSE', 'r'),
            ));

            $size = $content->reduce(
                0,
                static fn($i) => ++$i,
            );

            $assert->same(22, $size);
        },
    );

    yield test(
        'Content::oneShot()->toString()',
        static function($assert) use ($io) {
            $content = Model::oneShot($io->streams()->acquire(
                \fopen('LICENSE', 'r'),
            ));

            $assert->same(\file_get_contents('LICENSE'), $content->toString());
        },
    );

    yield test(
        'Content::oneShot()->chunks()',
        static function($assert) use ($io) {
            $content = Model::oneShot($io->streams()->acquire(
                \fopen('LICENSE', 'r'),
            ));

            $assert->same(
                \file_get_contents('LICENSE'),
                $content
                    ->chunks()
                    ->fold(new Concat)
                    ->toString(),
            );
        },
    );

    $actions = Set::of(
        static fn($content) => $content->foreach(static fn() => null),
        static fn($content) => $content->toString(),
        static fn($content) => $content->chunks()->toList(),
        static fn($content) => $content->lines()->toList(),
        static fn($content) => $content->reduce(null, static fn() => null),
        static fn($content) => $content->filter(static fn() => true)->toString(),
        static fn($content) => $content->map(static fn() => Str::of(''))->toString(),
        static fn($content) => $content
            ->flatMap(static fn() => Model::ofString(''))
            ->toString(),
    );

    yield proof(
        'Content::oneShot() throws when loaded multiple times',
        given($actions, $actions),
        static function($assert, $a, $b) use ($io) {
            $content = Model::oneShot($io->streams()->acquire(
                \fopen('LICENSE', 'r'),
            ));

            $a($content);
            $assert->throws(static fn() => $b($content));
        },
    );

    yield test(
        'Content::ofChunks()->size() does not load the whole file in memory',
        static function($assert) use ($io) {
            $atPath = Model::atPath(
                $io,
                Path::of('samples/sample.pdf'),
            );
            $content = Model::ofChunks($atPath->chunks());

            $assert
                ->memory(static function() use ($assert, $content) {
                    $size = $content->size()->match(
                        static fn($size) => $size->toInt(),
                        static fn() => null,
                    );
                    $assert->same(5951532, $size);
                })
                ->inLessThan()
                ->kilobytes(537);
        },
    );
};
