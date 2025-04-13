<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Content;

use Innmind\Filesystem\File\{
    Content,
    Content\Line,
};
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Content>
 */
final class ForeachExposeAllLines implements Property
{
    public static function any(): Set
    {
        return Set::of(new self);
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $count = 0;
        $systemUnderTest->foreach(static function($line) use ($assert, &$count) {
            $assert->object($line)->instance(Line::class);
            $count++;
        });
        $all = \count(\explode("\n", $systemUnderTest->toString()));

        $assert->same($all, $count);

        return $systemUnderTest;
    }
}
