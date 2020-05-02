<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Directory\Directory,
    File,
    Name,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddDirectoryFromAnotherAdapter implements Property
{
    private const NAME = 'Some directory from another adapter';

    public function name(): string
    {
        return 'Add directory loaded from another adapter';
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains(new Name(self::NAME));
    }

    public function ensureHeldBy(object $adapter): object
    {
        // directories loaded from other adapters have files injecting at
        // construct time (so there is no modifications())
        $directory = new Directory(
            new Name(self::NAME),
            Set::of(
                File::class,
                new File\File(
                    new Name('file from other adapter'),
                    Stream::ofContent('foobar'),
                ),
            ),
        );

        Assert::assertFalse($adapter->contains($directory->name()));
        Assert::assertNull($adapter->add($directory));
        Assert::assertTrue($adapter->contains($directory->name()));
        Assert::assertTrue(
            $adapter
                ->get($directory->name())
                ->contains(new Name('file from other adapter')),
        );
        Assert::assertSame(
            'foobar',
            $adapter
                ->get($directory->name())
                ->get(new Name('file from other adapter'))
                ->content()
                ->toString(),
        );

        return $adapter;
    }
}
