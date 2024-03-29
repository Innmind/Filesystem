<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\File as FFile;

/**
 * @implements Property<Adapter>
 */
final class RemoveFile implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public static function any(): Set
    {
        return FFile::any()->map(static fn($file) => new self($file));
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $assert->null($adapter->add($this->file));
        $assert->true($adapter->contains($this->file->name()));
        $assert->null($adapter->remove($this->file->name()));
        $assert->false($adapter->contains($this->file->name()));

        return $adapter;
    }
}
