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

final class AddDirectoryFromAnotherAdapterWithFileRemoved implements Property
{
    private Name $name;
    private File $file;
    private File $removed;

    public function __construct(Name $name, File $file, File $removed)
    {
        $this->name = $name;
        $this->file = $file;
        $this->removed = $removed;
    }

    public function name(): string
    {
        return "Add directory '{$this->name->toString()}' loaded from another adapter with file '{$this->removed->name()->toString()}' removed";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->name);
    }

    public function ensureHeldBy(object $adapter): object
    {
        // directories loaded from other adapters have files injecting at
        // construct time (so there is no modifications())
        $directory = new Directory(
            $this->name,
            Set::of(
                File::class,
                $this->removed,
                $this->file,
            ),
        );
        $directory = $directory->remove($this->removed->name());

        Assert::assertFalse($adapter->contains($directory->name()));
        Assert::assertNull($adapter->add($directory));
        Assert::assertTrue($adapter->contains($directory->name()));
        Assert::assertTrue(
            $adapter
                ->get($directory->name())
                ->contains($this->file->name()),
        );
        Assert::assertFalse(
            $adapter
                ->get($directory->name())
                ->contains($this->removed->name()),
        );
        Assert::assertSame(
            $this->file->content()->toString(),
            $adapter
                ->get($directory->name())
                ->get($this->file->name())
                ->content()
                ->toString(),
        );

        return $adapter;
    }
}
