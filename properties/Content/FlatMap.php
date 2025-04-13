<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Content;

use Innmind\Filesystem\File\Content;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Content>
 */
final class FlatMap implements Property
{
    private Content $content;

    public function __construct(Content $content)
    {
        $this->content = $content;
    }

    public static function any(): Set
    {
        return Set::strings()
            ->madeOf(Set::strings()->unicode()->char())
            ->map(Content::ofString(...))
            ->map(static fn($line) => new self($line));
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $content = $systemUnderTest->flatMap(fn() => $this->content);

        $size = $systemUnderTest->lines()->size();

        $assert->same(
            $size * $this->content->lines()->size(),
            $content->lines()->size(),
        );

        $assert->string($content->toString())->contains($this->content->toString());
        $assert->same(
            $this
                ->content
                ->lines()
                ->map(static fn($line) => $line->toString())
                ->distinct()
                ->toList(),
            $content
                ->lines()
                ->map(static fn($line) => $line->toString())
                ->distinct()
                ->toList(),
        );

        return $content;
    }
}
