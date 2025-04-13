<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
};
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\{
    Property,
    Set,
    Runner\Assert,
};
use Fixtures\Innmind\Filesystem\File as FFile;

/**
 * @implements Property<Adapter>
 */
final class AddFile implements Property
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
        return !$adapter->contains($this->file->name());
    }

    public function ensureHeldBy(Assert $assert, object $adapter): object
    {
        $assert->false($adapter->contains($this->file->name()));
        $assert
            ->object($adapter->add($this->file)->unwrap())
            ->instance(SideEffect::class);
        $assert->true($adapter->contains($this->file->name()));
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
