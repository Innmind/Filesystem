<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class MediaTypeIsAlwaysTheSame implements Property
{
    public function name(): string
    {
        return 'Directory media type is always the same';
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        Assert::assertSame(
            'text/directory',
            $directory->mediaType()->toString(),
        );

        return $directory;
    }
}
