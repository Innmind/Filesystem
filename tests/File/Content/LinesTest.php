<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\File\Content;

use Innmind\Filesystem\File\{
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

class LinesTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Content::class, Lines::of(Sequence::of()));
    }

    public function testForeach()
    {
        $this
            ->forAll(Set\Sequence::of($this->strings())->between(1, 10))
            ->then(function($lines) {
                $content = Lines::of(Sequence::of(...$lines));
                $called = 0;

                $this->assertInstanceOf(
                    SideEffect::class,
                    $content->foreach(function($line) use ($lines, &$called) {
                        $this->assertSame($lines[$called], $line);
                        $called++;
                    }),
                );
                $this->assertSame(\count($lines), $called);
            });
    }

    public function testForeachCalledOnceWhenEmptyContent()
    {
        $content = Lines::of(Sequence::of(Line::of(Str::of(''))));
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
                Set\Sequence::of($this->strings())->between(1, 10),
                $this->strings(),
            )
            ->then(function($lines, $replacement) {
                $content = Lines::of(Sequence::of(...$lines));
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
                Set\Sequence::of($this->strings())->between(1, 10),
                $this->strings(),
            )
            ->then(function($lines, $newLine) {
                $content = Lines::of(Sequence::of(...$lines));
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
                    $newContent .= $line->toString()."\n".$newLine->toString()."\n";
                }

                $this->assertSame(\substr($newContent, 0, -1), $extra->toString());
            });
    }

    public function testFilter()
    {
        $this
            ->forAll(Set\Sequence::of($this->strings())->between(1, 10))
            ->then(function($lines) {
                $content = Lines::of(Sequence::of(...$lines));
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
                Set\Sequence::of($this->strings())->between(1, 10),
                $this->strings(),
            )
            ->then(function($lines, $replacement) {
                $content = Lines::of(Sequence::of(...$lines));

                $called = 0;
                $sequence = $content->lines()->map(function($line) use ($lines, $replacement, &$called) {
                    $this->assertSame($lines[$called], $line);
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
            ->forAll(Set\Sequence::of($this->strings())->between(1, 10))
            ->then(function($lines) {
                $content = Lines::of(Sequence::of(...$lines));

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
            ->forAll(Set\Sequence::of($this->strings())->between(1, 10))
            ->then(function($lines) {
                $content = Lines::of(Sequence::of(...$lines));

                $this->assertSame(
                    \implode(
                        "\n",
                        \array_map(static fn($line) => $line->toString(), $lines),
                    ),
                    $content->toString(),
                );
            });
    }

    public function testSize()
    {
        $content = Lines::of(Sequence::of(
            Line::of(Str::of('')),
        ));
        $this->assertSame(0, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $content = Lines::of(Sequence::of(
            Line::of(Str::of('foo')),
        ));
        $this->assertSame(3, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $content = Lines::of(Sequence::of(
            Line::of(Str::of('foo')),
            Line::of(Str::of('foo')),
        ));
        $this->assertSame(7, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $content = Lines::of(Sequence::of(
            Line::of(Str::of('foo')),
            Line::of(Str::of('foo')),
            Line::of(Str::of('')),
        ));
        $this->assertSame(8, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $content = Lines::of(Sequence::of(
            Line::of(Str::of('foo')),
            Line::of(Str::of('')),
            Line::of(Str::of('foo')),
        ));
        $this->assertSame(8, $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => null,
        ));

        $this
            ->forAll(Set\Sequence::of($this->strings())->between(0, 10))
            ->then(function($lines) {
                $raw = \array_map(
                    static fn($line) => $line->toString(),
                    $lines,
                );
                $expectedSize = Str::of(\implode("\n", $raw), 'ASCII')->length();
                $content = Lines::of(Sequence::of(...$lines));

                $this->assertSame($expectedSize, $content->size()->match(
                    static fn($size) => $size->toInt(),
                    static fn() => null,
                ));
            });
    }

    private function strings(): Set
    {
        return Set\Decorate::immutable(
            static fn($string) => Line::of(Str::of($string)),
            Set\Decorate::immutable(
                static fn($line) => \rtrim($line, "\n"),
                Set\Unicode::strings(),
            )->filter(static fn($line) => !\str_contains($line, "\n")),
        );
    }
}
