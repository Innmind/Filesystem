<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Content;

use Innmind\Filesystem\File\Content;
use Innmind\Immutable\Monoid\Concat;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Content>
 */
final class Chunks implements Property
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
        $assert->same(
            $systemUnderTest->toString(),
            $systemUnderTest
                ->chunks()
                ->fold(new Concat)
                ->toString(),
        );
        $assert
            ->number($systemUnderTest->chunks()->size())
            ->greaterThanOrEqual(1);

        return $systemUnderTest;
    }
}
