<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File,
    Directory,
    Name,
};
use Innmind\Stream\Readable;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

/**
 * As strange as it may sound at first this property intends to provide the same
 * behaviour as if the existing name is a file, in this case we rewrite the file,
 * this should be same behaviour in the case the name points to a directory
 */
final class AddFileWithSameNameAsDirectoryDeleteTheDirectory implements Property
{
    private File $file;
    private Directory $directory;

    public function __construct(Name $name, Readable $file, File $fileInDirectory)
    {
        $this->file = new File\File($name, $file);
        // the extra file is here to make sure we can delete non empty directories
        $this->directory = (new Directory\Directory($name))->add($fileInDirectory);
    }

    public function name(): string
    {
        return "Add file '{$this->file->name()->toString()}' delete the existing directory with same name";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->file->name());
    }

    public function ensureHeldBy(object $adapter): object
    {
        Assert::assertFalse($adapter->contains($this->file->name()));
        Assert::assertNull($adapter->add($this->directory));
        Assert::assertNull($adapter->add($this->file));
        Assert::assertTrue($adapter->contains($this->file->name()));
        Assert::assertNotInstanceOf(Directory::class, $adapter->get($this->file->name()));
        Assert::assertSame(
            $this->file->content()->toString(),
            $adapter
                ->get($this->file->name())
                ->content()
                ->toString(),
        );

        return $adapter;
    }
}
