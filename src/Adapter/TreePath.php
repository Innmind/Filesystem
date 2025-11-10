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
    Maybe,
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

    /**
     * Name of the file the path points to. If no name it means the path
     * represent the root directory.
     *
     * @return Maybe<Name>
     */
    public function name(): Maybe
    {
        return $this->path->first();
    }

    /**
     * @template R
     *
     * @param callable(Name, self, bool): R $file
     * @param callable(): R $root
     *
     * @return R
     */
    public function match(
        callable $file,
        callable $root,
    ): mixed {
        return $this->path->match(
            fn($name, $parent) => $file(
                $name,
                new self($parent, true), // since there's a child the parent is necessarily a directory
                $this->directory,
            ),
            $root,
        );
    }
}
