<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\Name;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AllRootFilesAreAccessible implements Property
{
    public function name(): string
    {
        return 'All root files are accessible';
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(object $adapter): object
    {
        $adapter
            ->all()
            ->foreach(function($file) use ($adapter) {
                Assert::assertTrue($adapter->contains($file->name()));
                Assert::assertSame(
                    $file->content()->toString(),
                    $adapter
                        ->get($file->name())
                        ->content()
                        ->toString(),
                );
            });

        return $adapter;
    }
}
