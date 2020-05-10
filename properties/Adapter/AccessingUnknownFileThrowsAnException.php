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
    private Name $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return "Accessing unknown file '{$this->name->toString()}' must throw an exception";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->name);
    }

    public function ensureHeldBy(object $adapter): object
    {
        try {
            $adapter->get($this->name);

            Assert::fail('It should throw an exception');
        } catch (FileNotFound $e) {
            Assert::assertTrue(true);
        }

        return $adapter;
    }
}
