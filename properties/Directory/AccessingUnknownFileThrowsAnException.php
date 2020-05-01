<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Name,
    Exception\FileNotFound,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AccessingUnknownFileThrowsAnException implements Property
{
    private const UNKNOWN = 'some unknown file name';

    public function name(): string
    {
        return 'Accessing an unknown file throw an exception';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains(new Name(self::UNKNOWN));
    }

    public function ensureHeldBy(object $directory): object
    {
        try {
            $directory->get(new Name(self::UNKNOWN));

            Assert::fail('It should throw an exception');
        } catch (FileNotFound $e) {
            Assert::assertTrue(true);
        }

        return $directory;
    }
}
