<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Directory,
    File,
};
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddDirectory implements Property
{
    private Directory $directory;

    public function __construct(Directory $directory)
    {
        $this->directory = $directory;
    }

    public function name(): string
    {
        return "Add directory '{$this->directory->name()->toString()}'";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->directory->name());
    }

    public function ensureHeldBy(object $adapter): object
    {
        Assert::assertFalse($adapter->contains($this->directory->name()));
        Assert::assertNull($adapter->add($this->directory));
        Assert::assertTrue($adapter->contains($this->directory->name()));
        $this->assertSame(
            $this->directory,
            $adapter->get($this->directory->name()),
        );

        return $adapter;
    }

    private function assertSame(File $source, File $target): void
    {
        Assert::assertSame(
            $source->name()->toString(),
            $target->name()->toString(),
        );
        Assert::assertSame(
            $source->content()->toString(),
            $target->content()->toString(),
        );

        if ($target instanceof Directory) {
            $target->foreach(function($file) use ($source) {
                Assert::assertTrue($source->contains($file->name()));

                $this->assertSame(
                    $source->get($file->name()),
                    $file,
                );
            });
        }
    }
}
