<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\File\Content;

use Innmind\Filesystem\{
    File\Content\Line,
    Exception\DomainException,
};
use Innmind\Immutable\Str;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class LineTest extends TestCase
{
    use BlackBox;

    public function testDoesntAcceptNewLineDelimiter()
    {
        $this
            ->forAll(
                Set\Unicode::strings(),
                Set\Unicode::strings(),
            )
            ->then(function($start, $end) {
                try {
                    Line::of(Str::of($start."\n".$end));

                    $this->fail('it should throw');
                } catch (\Exception $e) {
                    $this->assertInstanceOf(DomainException::class, $e);
                }
            });
    }

    public function testLineIsNotAltered()
    {
        $this
            ->forAll($this->strings())
            ->then(function($content) {
                $this->assertSame($content, Line::of(Str::of($content))->toString());
            });
    }

    public function testEndOfLineDelimiterIsRemovedAutomaticallyWhenReadingFromStream()
    {
        $this
            ->forAll(Set\Unicode::strings())
            ->then(function($content) {
                $this->assertStringEndsNotWith(
                    "\n",
                    Line::fromStream(Str::of($content))->toString(),
                );
            });
    }

    public function testMap()
    {
        $this
            ->forAll(
                $this->strings(),
                $this->strings(),
            )
            ->then(function($original, $replacement) {
                $line = Line::of(Str::of($original));
                $mapped = $line->map(function($content) use ($original, $replacement) {
                    $this->assertSame($original, $content->toString());

                    return Str::of($replacement);
                });

                $this->assertNotSame($line, $mapped);
                $this->assertSame($original, $line->toString());
                $this->assertSame($replacement, $mapped->toString());
            });
    }

    public function testMappedLineCannotContainEndOfLineDelimiter()
    {
        $this
            ->forAll($this->strings())
            ->then(function($content) {
                $line = Line::of(Str::of($content));

                try {
                    $line->map(static fn($line) => $line->append("\n"));

                    $this->fail('it should throw');
                } catch (\Exception $e) {
                    $this->assertInstanceOf(DomainException::class, $e);
                }
            });
    }

    private function strings(): Set
    {
        return Set\Decorate::immutable(
            static fn($content) => \rtrim($content, "\n"),
            Set\Unicode::strings(),
        )->filter(static fn($content) => !\str_contains($content, "\n"));
    }
}
