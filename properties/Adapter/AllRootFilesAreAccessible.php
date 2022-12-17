<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

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
            ->root()
            ->files()
            ->foreach(static function($file) use ($adapter) {
                Assert::assertTrue($adapter->contains($file->name()));
                Assert::assertSame(
                    $file->content()->toString(),
                    $adapter
                        ->get($file->name())
                        ->map(static fn($file) => $file->content())
                        ->match(
                            static fn($content) => $content->toString(),
                            static fn() => null,
                        ),
                );
            });
        Assert::assertSame($adapter->root()->files()->size(), $adapter->all()->size());
        $adapter
            ->all()
            ->foreach(static function($file) use ($adapter) {
                Assert::assertSame(
                    $file->content()->toString(),
                    $adapter
                        ->root()
                        ->get($file->name())
                        ->map(static fn($file) => $file->content())
                        ->match(
                            static fn($content) => $content->toString(),
                            static fn() => null,
                        ),
                );
            });

        return $adapter;
    }
}
