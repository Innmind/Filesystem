<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Content;

use Innmind\Filesystem\File\Content;
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Content>
 */
final class ForeachExposeAtLeastOneLine implements Property
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
        $count = 0;
        $return = $systemUnderTest->foreach(static function($line) use (&$count) {
            $count++;
        });

        $assert->object($return)->instance(SideEffect::class);
        $assert->number($count)->greaterThanOrEqual(1);

        return $systemUnderTest;
    }
}
