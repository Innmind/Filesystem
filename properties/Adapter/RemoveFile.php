<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\File;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class RemoveFile implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function name(): string
    {
        return "Remove file '{$this->file->name()->toString()}'";
    }

    public function applicableTo(object $adapter): bool
    {
        return true;
    }

    public function ensureHeldBy(object $adapter): object
    {
        Assert::assertNull($adapter->add($this->file));
        Assert::assertTrue($adapter->contains($this->file->name()));
        Assert::assertNull($adapter->remove($this->file->name()));
        Assert::assertFalse($adapter->contains($this->file->name()));

        return $adapter;
    }
}
