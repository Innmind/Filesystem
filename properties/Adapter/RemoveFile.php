<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File\File,
    Name,
};
use Innmind\Stream\Readable\Stream;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemoveFile implements Property
{
    private const NAME = 'Some file to be removed';

    public function name(): string
    {
        return 'Remove file';
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(object $adapter): object
    {
        $file = new File(
            new Name(self::NAME),
            Stream::ofContent('foo'),
        );

        Assert::assertNull($adapter->add($file));
        Assert::assertTrue($adapter->contains($file->name()));
        Assert::assertNull($adapter->remove($file->name()));
        Assert::assertFalse($adapter->contains($file->name()));

        return $adapter;
    }
}
