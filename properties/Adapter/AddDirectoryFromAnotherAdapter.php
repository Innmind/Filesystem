<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    Directory,
    File,
    Name,
};
use Innmind\Immutable\{
    Sequence,
    SideEffect,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\{
    Name as FName,
    File as FFile,
};

/**
 * @implements Property<Adapter>
 */
final class AddDirectoryFromAnotherAdapter implements Property
{
    private Name $name;
    private File $file;

    public function __construct(Name $name, File $file)
    {
        $this->name = $name;
        $this->file = $file;
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            FName::any(),
            FFile::any(),
        );
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->name);
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        // directories loaded from other adapters have files injected at
        // construct time (so there is no modifications())
        $directory = Directory::of(
            $this->name,
            Sequence::of($this->file),
        );

        $assert->false($adapter->contains($directory->name()));
        $assert
            ->object($adapter->add($directory)->unwrap())
            ->instance(SideEffect::class);
        $assert->true($adapter->contains($directory->name()));
        $assert->true(
            $adapter->get($directory->name())->match(
                fn($dir) => $dir->contains($this->file->name()),
                static fn() => false,
            ),
        );
        $assert->same(
            $this->file->content()->toString(),
            $adapter
                ->get($directory->name())
                ->flatMap(fn($dir) => $dir->get($this->file->name()))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        return $adapter;
    }
}
