<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Directory,
    Name,
};
use Innmind\Immutable\Set;
use Innmind\BlackBox\{
    Property,
    Set as DataSet,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\File as FFile;
use Ramsey\Uuid\Uuid;

/**
 * @implements Property<Directory>
 */
final class FlatMapFiles implements Property
{
    private File $file1;
    private File $file2;

    public function __construct(File $file1, File $file2)
    {
        $this->file1 = $file1;
        $this->file2 = $file2;
    }

    public static function any(): DataSet
    {
        return DataSet\Composite::immutable(
            static fn(...$args) => new self(...$args),
            DataSet\Randomize::of(FFile::any()),
            DataSet\Randomize::of(FFile::any()),
        );
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->files()->empty();
    }

    public function ensureHeldBy(Assert $assert, object $directory): object
    {
        // we use uuids to avoid duplicates
        $directory2 = $directory->flatMap(fn($file) => Directory\Directory::of(
            Name::of('doesntmatter'),
            Set::of(
                $this->file1->rename(Name::of(Uuid::uuid4()->toString())),
                $this->file2->rename(Name::of(Uuid::uuid4()->toString())),
            ),
        ));

        $assert
            ->expected($directory)
            ->not()
            ->same($directory2);
        $assert->same($directory->name()->toString(), $directory2->name()->toString());
        $assert
            ->expected($directory->files())
            ->not()
            ->same($directory2->files());
        $assert->same($directory->files()->size() * 2, $directory2->files()->size());
        $assert->same(
            [$this->file1->content(), $this->file2->content()],
            $directory2
                ->files()
                ->map(static fn($file) => $file->content())
                ->distinct()
                ->toList(),
        );

        return $directory2;
    }
}
