<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    Directory,
    File,
};
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

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->directory->name());
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $assert->false($adapter->contains($this->directory->name()));
        $assert->null($adapter->add($this->directory));
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

    private function assertSame(Assert $assert, File $source, File $target): void
    {
        $assert->same(
            $source->name()->toString(),
            $target->name()->toString(),
        );
        $assert->same(
            $source->content()->toString(),
            $target->content()->toString(),
        );

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
