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
use Fixtures\Innmind\Filesystem\{
    Directory as FDirectory,
    File as FFile,
};

/**
 * @implements Property<Adapter>
 */
final class AddRemoveAddModificationsStillAddTheFile implements Property
{
    private Directory $directory;
    private File $file;

    public function __construct(Directory $directory, File $file)
    {
        $this->directory = $directory;
        $this->file = $file;
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            FDirectory::any(),
            FFile::any(),
        );
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->directory->name());
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $adapter
            ->add(
                $this
                    ->directory
                    ->add($this->file)
                    ->remove($this->file->name())
                    ->add($this->file),
            )
            ->unwrap();
        $assert->true(
            $adapter->get($this->directory->name())->match(
                fn($dir) => $dir->contains($this->file->name()),
                static fn() => false,
            ),
        );

        return $adapter;
    }
}
