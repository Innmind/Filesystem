<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Directory\Directory,
    File,
    Name,
};
use Innmind\Immutable\Set;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddDirectoryFromAnotherAdapterWithFileAdded implements Property
{
    private Name $name;
    private File $file;
    private File $added;

    public function __construct(Name $name, File $file, File $added)
    {
        $this->name = $name;
        $this->file = $file;
        $this->added = $added;
    }

    public function name(): string
    {
        return "Add directory '{$this->name->toString()}' loaded from another adapter with file '{$this->added->name()->toString()}' added";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->name);
    }

    public function ensureHeldBy(object $adapter): object
    {
        // directories loaded from other adapters have files injected at
        // construct time (so there is no modifications())
        $directory = Directory::of(
            $this->name,
            Set::of(
                File::class,
                $this->file,
            ),
        );
        $directory = $directory->add($this->added);

        Assert::assertFalse($adapter->contains($directory->name()));
        Assert::assertNull($adapter->add($directory));
        Assert::assertTrue($adapter->contains($directory->name()));
        Assert::assertTrue(
            $adapter->get($directory->name())->match(
                fn($dir) => $dir->contains($this->file->name()),
                static fn() => false,
            ),
        );
        Assert::assertTrue(
            $adapter->get($directory->name())->match(
                fn($dir) => $dir->contains($this->added->name()),
                static fn() => false,
            ),
        );
        Assert::assertSame(
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
        Assert::assertSame(
            $this->added->content()->toString(),
            $adapter
                ->get($directory->name())
                ->flatMap(fn($dir) => $dir->get($this->added->name()))
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        return $adapter;
    }
}
