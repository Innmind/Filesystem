<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    Directory,
    File,
};
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\Directory as FDirectory;

/**
 * @implements Property<Adapter>
 */
final class AddDirectory implements Property
{
    private Directory $directory;

    public function __construct(Directory $directory)
    {
        $this->directory = $directory;
    }

    public static function any(): Set
    {
        return FDirectory::any()->map(static fn($directory) => new self($directory));
    }

    public function directory(): Directory
    {
        return $this->directory;
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->directory->name());
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $assert->false($adapter->contains($this->directory->name()));
        $assert
            ->object($adapter->add($this->directory)->unwrap())
            ->instance(SideEffect::class);
        $assert->true($adapter->contains($this->directory->name()));
        $this->assertSame(
            $assert,
            $this->directory,
            $adapter->get($this->directory->name())->match(
                static fn($file) => $file,
                static fn() => null,
            ),
        );

        return $adapter;
    }

    private function assertSame(
        Assert $assert,
        File|Directory $source,
        File|Directory $target,
    ): void {
        $assert->same(
            $source->name()->toString(),
            $target->name()->toString(),
        );

        if ($source instanceof File) {
            $assert->same(
                $source->content()->toString(),
                $target->content()->toString(),
            );
        }

        if ($target instanceof Directory) {
            $target->foreach(function($file) use ($assert, $source) {
                $assert->true($source->contains($file->name()));

                $this->assertSame(
                    $assert,
                    $source->get($file->name())->match(
                        static fn($file) => $file,
                        static fn() => null,
                    ),
                    $file,
                );
            });
        }
    }
}
