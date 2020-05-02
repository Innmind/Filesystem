<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Name,
    Exception\FileNotFound,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AccessingUnknownFileThrowsAnException implements Property
{
    private const NAME = 'Unknown file';

    public function name(): string
    {
        return 'Accessing an unknown file throws an exception';
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains(new Name(self::NAME));
    }

    public function ensureHeldBy(object $adapter): object
    {
        try {
            $adapter->get(new Name(self::NAME));

            Assert::fail('It should throw an exception');
        } catch (FileNotFound $e) {
            Assert::assertTrue(true);
        }

        return $adapter;
    }
}
