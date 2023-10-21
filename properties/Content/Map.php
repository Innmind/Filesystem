<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Content;

use Innmind\Filesystem\File\{
    Content,
    Content\Line,
};
use Innmind\Immutable\Str;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Content>
 */
final class Map implements Property
{
    private Line $replacement;

    public function __construct(Line $replacement)
    {
        $this->replacement = $replacement;
    }

    public static function any(): Set
    {
        return Set\Strings::madeOf(
            Set\Unicode::any()->filter(static fn($char) => $char !== "\n"),
        )
            ->map(Str::of(...))
            ->map(Line::of(...))
            ->map(static fn($line) => new self($line));
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $content = $systemUnderTest->map(fn() => $this->replacement);

        $size = $systemUnderTest->lines()->size();

        $assert->same($size, $content->lines()->size());

        $assert->same(
            [$this->replacement->toString()],
            $content
                ->lines()
                ->map(static fn($line) => $line->toString())
                ->distinct()
                ->toList(),
        );

        return $content;
    }
}
