<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\File\Content;

use Innmind\Filesystem\File\Content\Line;
use Innmind\Immutable\Str;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class LineTest extends TestCase
{
    use BlackBox;

    public function testDoesntAcceptNewLineDelimiter(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::strings()->unicode(),
                Set::strings()->unicode(),
            )
            ->prove(function($start, $end) {
                try {
                    Line::of(Str::of($start."\n".$end));

                    $this->fail('it should throw');
                } catch (\Exception $e) {
                    $this->assertInstanceOf(\DomainException::class, $e);
                }
            });
    }

    public function testLineIsNotAltered(): BlackBox\Proof
    {
        return $this
            ->forAll($this->strings())
            ->prove(function($content) {
                $this->assertSame($content, Line::of(Str::of($content))->toString());
            });
    }

    public function testEndOfLineDelimiterIsRemovedAutomaticallyWhenReadingFromStream(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::strings()->unicode())
            ->prove(function($content) {
                $this->assertStringEndsNotWith(
                    "\n",
                    Line::fromStream(Str::of($content))->toString(),
                );
            });
    }

    public function testMap(): BlackBox\Proof
    {
        return $this
            ->forAll(
                $this->strings(),
                $this->strings(),
            )
            ->prove(function($original, $replacement) {
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

    public function testMappedLineCannotContainEndOfLineDelimiter(): BlackBox\Proof
    {
        return $this
            ->forAll($this->strings())
            ->prove(function($content) {
                $line = Line::of(Str::of($content));

                try {
                    $line->map(static fn($line) => $line->append("\n"));

                    $this->fail('it should throw');
                } catch (\Exception $e) {
                    $this->assertInstanceOf(\DomainException::class, $e);
                }
            });
    }

    private function strings(): Set
    {
        return Set::strings()
            ->unicode()
            ->map(static fn($content) => \rtrim($content, "\n"))
            ->filter(static fn($content) => !\str_contains($content, "\n"));
    }
}
