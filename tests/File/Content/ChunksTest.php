<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\File\Content;

use Innmind\Filesystem\File\{
    Content\Chunks,
    Content\Chunkable,
    Content\Lines,
    Content\Line,
    Content,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    SideEffect,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ChunksTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Content::class, Chunks::of(Sequence::of()));
        $this->assertInstanceOf(Chunkable::class, Chunks::of(Sequence::of()));
    }

    public function testForeach()
    {
        $this
            ->forAll($this->chunks())
            ->then(function($chunks) {
                $content = Chunks::of(Sequence::of(...$chunks)->map(Str::of(...)));
                $lines = $this->lines($chunks);
                $called = 0;

                $this->assertInstanceOf(
                    SideEffect::class,
                    $content->foreach(function($line) use ($lines, &$called) {
                        $this->assertSame($lines[$called], $line->toString());
                        $called++;
                    }),
                );
            });
    }

    public function testForeachCalledOnceWhenEmptyContent()
    {
        $content = Chunks::of(Sequence::of(Str::of('')));
        $called = 0;

        $this->assertInstanceOf(
            SideEffect::class,
            $content->foreach(function($line) use (&$called) {
                $this->assertSame('', $line->toString());
                $called++;
            }),
        );
        $this->assertSame(1, $called);
    }

    public function testMap()
    {
        $this
            ->forAll(
                $this->chunks(),
                $this->strings(),
            )
            ->then(function($chunks, $replacement) {
                $content = Chunks::of(Sequence::of(...$chunks)->map(Str::of(...)));
                $lines = $this->lines($chunks);
                $mapped = $content->map(static fn() => $replacement);
                $called = 0;

                $this->assertNotSame($content, $mapped);
                $this->assertInstanceOf(
                    SideEffect::class,
                    $mapped->foreach(function($line) use ($replacement, &$called) {
                        $this->assertSame($replacement, $line);
                        $called++;
                    }),
                );
                $this->assertSame(\count($lines), $called);
            });
    }

    public function testFlatMap()
    {
        $this
            ->forAll(
                $this->chunks(),
                $this->strings(),
            )
            ->then(function($chunks, $newLine) {
                $content = Chunks::of(Sequence::of(...$chunks)->map(Str::of(...)));
                $lines = $this->lines($chunks);
                $empty = $content->flatMap(static fn() => Lines::of(Sequence::of()));
                $extra = $content->flatMap(static fn($line) => Lines::of(Sequence::of(
                    $line,
                    $newLine,
                )));

                $called = 0;
                $empty->foreach(static function() use (&$called) {
                    ++$called;
                });
                $this->assertSame(0, $called);

                $called = 0;
                $extra->foreach(static function() use (&$called) {
                    ++$called;
                });
                $this->assertSame(\count($lines) * 2, $called);
                $newContent = '';

                foreach ($lines as $line) {
                    $newContent .= $line."\n".$newLine->toString()."\n";
                }

                $this->assertSame(\substr($newContent, 0, -1), $extra->toString());
            });
    }

    public function testFilter()
    {
        $this
            ->forAll($this->chunks())
            ->then(function($chunks) {
                $content = Chunks::of(Sequence::of(...$chunks)->map(Str::of(...)));
                $lines = $this->lines($chunks);
                $shouldBeEmpty = $content->filter(static fn() => false);
                $shouldBeTheSame = $content->filter(static fn() => true);

                $called = 0;
                $shouldBeEmpty->foreach(static function() use (&$called) {
                    ++$called;
                });
                $this->assertSame(0, $called);

                $called = 0;
                $shouldBeTheSame->foreach(static function() use (&$called) {
                    ++$called;
                });
                $this->assertSame(\count($lines), $called);
            });
    }

    public function testLines()
    {
        $this
            ->forAll(
                $this->chunks(),
                $this->strings(),
            )
            ->then(function($chunks, $replacement) {
                $content = Chunks::of(Sequence::of(...$chunks)->map(Str::of(...)));
                $lines = $this->lines($chunks);

                $called = 0;
                $sequence = $content->lines()->map(function($line) use ($lines, $replacement, &$called) {
                    $this->assertSame($lines[$called], $line->toString());
                    $called++;

                    return $replacement;
                });

                $this->assertInstanceOf(Sequence::class, $sequence);
                $sequence->foreach(function($line) use ($replacement) {
                    $this->assertSame($replacement, $line);
                });
                $this->assertSame(\count($lines), $sequence->size());
            });
    }

    public function testReduce()
    {
        $this
            ->forAll($this->chunks())
            ->then(function($chunks) {
                $content = Chunks::of(Sequence::of(...$chunks)->map(Str::of(...)));
                $lines = $this->lines($chunks);

                $this->assertSame(
                    \count($lines),
                    $content->reduce(
                        0,
                        static fn($carry, $_) => $carry + 1,
                    ),
                );
            });
    }

    public function testToString()
    {
        $this
            ->forAll($this->chunks())
            ->then(function($chunks) {
                $content = Chunks::of(Sequence::of(...$chunks)->map(Str::of(...)));

                $this->assertSame(
                    \implode('', $chunks),
                    $content->toString(),
                );
            });
    }

    public function testSize()
    {
        $content = Chunks::of(Sequence::of(
            Str::of(''),
        ));
        $this->assertSame(0, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $content = Chunks::of(Sequence::of(
            Str::of('foo'),
        ));
        $this->assertSame(3, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $content = Chunks::of(Sequence::of(
            Str::of("foo\n"),
            Str::of('foo'),
        ));
        $this->assertSame(7, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $content = Chunks::of(Sequence::of(
            Str::of("foo\n"),
            Str::of("foo\n"),
            Str::of(''),
        ));
        $this->assertSame(8, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $content = Chunks::of(Sequence::of(
            Str::of("foo\n"),
            Str::of("\n"),
            Str::of('foo'),
        ));
        $this->assertSame(8, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $this
            ->forAll(Set\Sequence::of(
                Set\Unicode::strings(),
            )->between(0, 10))
            ->then(function($chunks) {
                $expectedSize = Str::of(\implode('', $chunks), Str\Encoding::ascii)->length();
                $content = Chunks::of(Sequence::of(...$chunks)->map(Str::of(...)));

                $this->assertSame($expectedSize, $content->size()->match(
                    static fn($size) => $size->toInt(),
                    static fn() => null,
                ));
            });
    }

    public function testFunctional()
    {
        $this
            ->forAll(Set\Integers::between(1, 10))
            ->then(function($size) {
                $source = "foo\nbar\nbaz\nwatev";
                $content = Chunks::of(
                    Str::of($source)->chunk($size),
                );

                $this->assertSame($source, $content->toString());
                $this->assertSame(
                    ['foo', 'bar', 'baz', 'watev'],
                    $content
                        ->lines()
                        ->map(static fn($line) => $line->toString())
                        ->toList(),
                );
            });
    }

    private function lines(array $chunks): array
    {
        return \explode("\n", \implode('', $chunks));
    }

    private function chunks(): Set
    {
        return Set\Sequence::of(
            Set\Unicode::strings(),
        )->between(1, 10);
    }

    private function strings(): Set
    {
        return Set\Decorate::immutable(
            Str::of(...),
            Set\Unicode::strings(),
        );
    }
}
