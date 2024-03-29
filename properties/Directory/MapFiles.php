<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory,
    File,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\File as FFile;

/**
 * @implements Property<Directory>
 */
final class MapFiles implements Property
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

    public function applicableTo(object $directory): bool
    {
        return !$directory->all()->empty();
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        $directory2 = $directory->map(fn($file) => $this->file->rename($file->name()));

        $assert
            ->expected($directory)
            ->not()
            ->same($directory2);
        $assert->same($directory->name()->toString(), $directory2->name()->toString());
        $assert
            ->expected($directory->all())
            ->not()
            ->same($directory2->all());
        $assert->same($directory->all()->size(), $directory2->all()->size());
        $assert->same(
            [$this->file->content()],
            $directory2
                ->all()
                ->map(static fn($file) => $file->content())
                ->distinct()
                ->toList(),
        );
        $assert->same($directory->removed(), $directory2->removed());

        return $directory2;
    }
}
