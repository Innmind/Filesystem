<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\File\Content;

use Innmind\Filesystem\File\{
    Content\OfStream,
    Content\Lines,
    Content\None,
    Content\Line,
    Content,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable\Stream;
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

class OfStreamTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Content::class, OfStream::of(Stream::open(Path::of('/dev/null'))));
    }

    public function testForeach()
    {
        $this
            ->forAll(Set\Sequence::of(
                $this->strings(),
                Set\Integers::between(1, 10),
            ))
            ->then(function($lines) {
                \file_put_contents('/tmp/test_content', \implode("\n", $lines));
                $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));
                $called = 0;

                $this->assertInstanceOf(
                    SideEffect::class,
                    $content->foreach(function($line) use ($lines, &$called) {
                        $this->assertSame($lines[$called], $line->toString());
                        $called++;
                    }),
                );
                $this->assertSame(\count($lines), $called);
            });
    }

    public function testForeachCalledOnceWhenEmptyContent()
    {
        \file_put_contents('/tmp/test_content', '');
        $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));
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
                Set\Sequence::of(
                    $this->strings(),
                    Set\Integers::between(1, 10),
                ),
                $this->strings(),
            )
            ->then(function($lines, $replacement) {
                $replacement = Line::of(Str::of($replacement));
                \file_put_contents('/tmp/test_content', \implode("\n", $lines));
                $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));
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
                Set\Sequence::of(
                    $this->strings(),
                    Set\Integers::between(1, 10),
                ),
                $this->strings(),
            )
            ->then(function($lines, $newLine) {
                $newLine = Line::of(Str::of($newLine));
                \file_put_contents('/tmp/test_content', \implode("\n", $lines));
                $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));
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
            ->forAll(Set\Sequence::of(
                $this->strings(),
                Set\Integers::between(1, 10),
            ))
            ->then(function($lines) {
                \file_put_contents('/tmp/test_content', \implode("\n", $lines));
                $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));
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

    public function testTransform()
    {
        $this
            ->forAll(
                Set\Sequence::of(
                    $this->strings(),
                    Set\Integers::between(1, 10),
                ),
                $this->strings(),
            )
            ->then(function($lines, $replacement) {
                $replacement = Line::of(Str::of($replacement));
                \file_put_contents('/tmp/test_content', \implode("\n", $lines));
                $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));

                $called = 0;
                $sequence = $content->transform(function($line) use ($lines, $replacement, &$called) {
                    $this->assertSame($lines[$called], $line->toString());
                    $called++;

                    return $replacement;
                });

                $this->assertInstanceOf(Sequence::class, $sequence);
                $size = 0;
                $sequence->foreach(function($line) use ($replacement, &$size) {
                    $this->assertSame($replacement, $line);
                    $size++;
                });
                // we don't call $sequence->size() as the sequence is lazy so it
                // would perform the transformation above twice resulting in an
                // error due to &$called being incremented above the number of
                // lines
                $this->assertSame(\count($lines), $size);
            });
    }

    public function testReduce()
    {
        $this
            ->forAll(Set\Sequence::of(
                $this->strings(),
                Set\Integers::between(1, 10),
            ))
            ->then(function($lines) {
                \file_put_contents('/tmp/test_content', \implode("\n", $lines));
                $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));

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
            ->forAll(Set\Sequence::of(
                $this->strings(),
                Set\Integers::between(1, 10),
            ))
            ->then(function($lines) {
                \file_put_contents('/tmp/test_content', \implode("\n", $lines));
                $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));

                $this->assertSame(\implode("\n", $lines), $content->toString());
            });
    }

    public function testSize()
    {
        $this
            ->forAll(Set\Sequence::of(
                $this->strings(),
                Set\Integers::between(0, 10),
            ))
            ->then(function($lines) {
                $expectedSize = Str::of(\implode("\n", $lines))->toEncoding('ASCII')->length();
                \file_put_contents('/tmp/test_content', \implode("\n", $lines));
                $content = OfStream::of(Stream::open(Path::of('/tmp/test_content')));

                $this->assertSame(
                    $expectedSize,
                    $content->size()->match(
                        static fn($size) => $size->toInt(),
                        static fn() => null,
                    ),
                );
            });
    }

    private function strings(): Set
    {
        return Set\Decorate::immutable(
            static fn($line) => \rtrim($line, "\n"),
            Set\Unicode::strings(),
        )->filter(static fn($line) => !\str_contains($line, "\n"));
    }
}
