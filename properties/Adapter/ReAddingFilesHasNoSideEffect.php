<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\Name;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ReAddingFilesHasNoSideEffect implements Property
{
    public function name(): string
    {
        return 'Re-adding files has no side effect';
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
                $adapter->add($file);
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
