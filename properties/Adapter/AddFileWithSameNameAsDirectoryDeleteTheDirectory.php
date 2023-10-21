<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
};
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\File as FFile;

/**
 * As strange as it may sound at first this property intends to provide the same
 * behaviour as if the existing name is a file, in this case we rewrite the file,
 * this should be same behaviour in the case the name points to a directory
 *
 * @implements Property<Adapter>
 */
final class AddFileWithSameNameAsDirectoryDeleteTheDirectory implements Property
{
    private File $file;
    private Directory $directory;

    public function __construct(File $file, File $fileInDirectory)
    {
        $this->file = $file;
        // the extra file is here to make sure we can delete non empty directories
        $this->directory = Directory::of($file->name())->add($fileInDirectory);
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            FFile::any(),
            FFile::any(),
        );
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->file->name());
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $assert->false($adapter->contains($this->file->name()));
        $assert->null($adapter->add($this->directory));
        $assert->null($adapter->add($this->file));
        $assert->true($adapter->contains($this->file->name()));
        $assert
            ->object($adapter->get($this->file->name())->match(
                static fn($file) => $file,
                static fn() => null,
            ))
            ->not()
            ->instance(Directory::class);
        $assert->same(
            $this->file->content()->toString(),
            $adapter
                ->get($this->file->name())
                ->map(static fn($file) => $file->content())
                ->match(
                    static fn($content) => $content->toString(),
                    static fn() => null,
                ),
        );

        return $adapter;
    }
}
