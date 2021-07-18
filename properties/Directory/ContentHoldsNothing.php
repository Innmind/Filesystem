<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ContentHoldsNothing implements Property
{
    public function name(): string
    {
        return 'Content stream holds nothing';
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        Assert::assertSame('', $directory->content()->toString());

        return $directory;
    }
}
