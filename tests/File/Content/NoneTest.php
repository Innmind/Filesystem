<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\File\Content;

use Innmind\Filesystem\File\{
    Content\None,
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

class NoneTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Content::class, None::of());
    }

    public function testForeachCalledOnce()
    {
        $content = None::of();
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
            ->forAll($this->strings())
            ->then(function($replacement) {
                $replacement = Line::of(Str::of($replacement));
                $content = None::of();
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
                $this->assertSame(1, $called);
            });
    }

    public function testFilter()
    {
        $content = None::of();
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
        $this->assertSame(1, $called);
    }

    public function testTransform()
    {
        $this
            ->forAll($this->strings())
            ->then(function($replacement) {
                $replacement = Line::of(Str::of($replacement));
                $content = None::of();

                $called = 0;
                $sequence = $content->transform(function($line) use ($replacement, &$called) {
                    $this->assertSame('', $line->toString());
                    $called++;

                    return $replacement;
                });

                $this->assertInstanceOf(Sequence::class, $sequence);
                $sequence->foreach(function($line) use ($replacement) {
                    $this->assertSame($replacement, $line);
                });
                $this->assertSame(1, $sequence->size());
            });
    }

    public function testReduce()
    {
        $content = None::of();

        $this->assertSame(
            1,
            $content->reduce(
                0,
                static fn($carry, $_) => $carry + 1,
            ),
        );
    }

    public function testToString()
    {
        $this->assertSame('', None::of()->toString());
    }

    private function strings(): Set
    {
        return Set\Decorate::immutable(
            static fn($line) => \rtrim($line, "\n"),
            Set\Unicode::strings(),
        )->filter(static fn($line) => !\str_contains($line, "\n"));
    }
}
