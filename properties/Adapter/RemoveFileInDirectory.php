<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Directory,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\{
    File as FFile,
    Name as FName,
};

/**
 * @implements Property<Adapter>
 */
final class RemoveFileInDirectory implements Property
{
    private File $file;
    private Name $name;

    public function __construct(File $file, Name $name)
    {
        $this->file = $file;
        $this->name = $name;
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            FFile::any(),
            FName::any(),
        );
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $assert->null($adapter->add(Directory::of($this->name)->add($this->file)));
        $assert->null($adapter->add(Directory::of($this->name)->remove($this->file->name())));
        $assert->false(
            $adapter
                ->get($this->name)
                ->match(
                    fn($directory) => $directory->contains($this->file->name()),
                    static fn() => null,
                ),
        );

        return $adapter;
    }
}
