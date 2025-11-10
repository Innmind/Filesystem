<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File,
    Directory,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Monoid\Concat,
};

/**
 * @internal
 * @psalm-immutable
 */
final class TreePath
{
    /**
     * @param Sequence<Name> $path
     */
    private function __construct(
        private Sequence $path,
        private bool $directory,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Name|File|Directory $file): self
    {
        return new self(
            Sequence::of(match (true) {
                $file instanceof Name => $file,
                default => $file->name(),
            }),
            $file instanceof Directory,
        );
    }

    /**
     * @psalm-pure
     */
    public static function directory(Name $file): self
    {
        return new self(
            Sequence::of($file),
            true,
        );
    }

    /**
     * @psalm-pure
     */
    public static function root(): self
    {
        return new self(
            Sequence::of(),
            false,
        );
    }

    public function under(self $parent): self
    {
        return new self(
            $this->path->append($parent->path),
            $this->directory,
        );
    }

    public function asPath(Path $root): Path
    {
        if ($this->path->empty()) {
            return $root;
        }

        $path = $this
            ->path
            ->reverse()
            ->map(static fn($name) => $name->str()->append('/'))
            ->fold(new Concat);

        if (!$this->directory) {
            $path = $path->dropEnd(1); // remove trailing '/'
        }

        return $root->resolve(Path::of($path->toString()));
    }
}
