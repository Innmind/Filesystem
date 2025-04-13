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
final class Lines implements Property
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
        $content = $assert->string($systemUnderTest->toString());
        $systemUnderTest
            ->lines()
            ->foreach(static fn($line) => $content->contains($line->toString()));

        return $systemUnderTest;
    }
}
