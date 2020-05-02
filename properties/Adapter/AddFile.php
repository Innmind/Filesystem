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

final class AddFile implements Property
{
    private const NAME = 'Some new file';

    public function name(): string
    {
        return 'Add file';
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains(new Name(self::NAME));
    }

    public function ensureHeldBy(object $adapter): object
    {
        $file = new File(
            new Name(self::NAME),
            Stream::ofContent('foo'),
        );

        Assert::assertFalse($adapter->contains($file->name()));
        Assert::assertNull($adapter->add($file));
        Assert::assertTrue($adapter->contains($file->name()));
        Assert::assertSame(
            'foo',
            $adapter
                ->get($file->name())
                ->content()
                ->toString(),
        );

        return $adapter;
    }
}
