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
final class Filter implements Property
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
        $none = $systemUnderTest->filter(static fn() => false);
        $all = $systemUnderTest->filter(static fn() => true);

        $assert->same(1, $none->lines()->size());
        $assert->same(1, $none->chunks()->size());
        $assert->same(
            [''],
            $none
                ->lines()
                ->map(static fn($line) => $line->toString())
                ->toList(),
        );
        $assert->same(
            [''],
            $none
                ->chunks()
                ->map(static fn($line) => $line->toString())
                ->toList(),
        );
        $assert->same(
            $systemUnderTest->lines()->size(),
            $all->lines()->size(),
        );
        $assert->same(
            $systemUnderTest->toString(),
            $all->toString(),
        );

        return $all;
    }
}
