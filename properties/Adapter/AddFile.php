<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\File;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class AddFile implements Property
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function name(): string
    {
        return "Add file '{$this->file->name()->toString()}'";
    }

    public function applicableTo(object $adapter): bool
    {
        return !$adapter->contains($this->file->name());
    }

    public function ensureHeldBy(object $adapter): object
    {
        Assert::assertFalse($adapter->contains($this->file->name()));
        Assert::assertNull($adapter->add($this->file));
        Assert::assertTrue($adapter->contains($this->file->name()));
        Assert::assertSame(
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
