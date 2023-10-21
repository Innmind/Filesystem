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
final class Reduce implements Property
{
    public static function any(): Set
    {
        return Set\Elements::of(new self);
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $content = $assert->string($systemUnderTest->toString());

        $count = $systemUnderTest->reduce(
            0,
            static function(int $count, $line) use ($content) {
                $content->contains($line->toString());

                return ++$count;
            },
        );

        $assert->same($systemUnderTest->lines()->size(), $count);

        return $systemUnderTest;
    }
}
