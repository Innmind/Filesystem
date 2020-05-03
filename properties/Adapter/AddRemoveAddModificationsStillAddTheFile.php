<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Directory,
    File,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddRemoveAddModificationsStillAddTheFile implements Property
{
    private Directory $directory;
    private File $file;

    public function __construct(Directory $directory, File $file)
    {
        $this->directory = $directory;
        $this->file = $file;
    }

    public function name(): string
    {
        return "Add/remove/add still add the file '{$this->file->name()->toString()}'";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->directory->name());
    }

    public function ensureHeldBy(object $adapter): object
    {
        $adapter->add(
            $this
                ->directory
                ->add($this->file)
                ->remove($this->file->name())
                ->add($this->file),
        );
        Assert::assertTrue(
            $adapter
                ->get($this->directory->name())
                ->contains($this->file->name()),
        );

        return $adapter;
    }
}
