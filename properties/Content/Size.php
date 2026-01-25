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
final class Size implements Property
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
        $expected = \mb_strlen($systemUnderTest->toString(), 'ascii');
        $size = $systemUnderTest
            ->size()
            ->match(
                static fn($size) => $size->toInt(),
                static fn() => null,
            );
        $assert->same($expected, $size);

        return $systemUnderTest;
    }
}
