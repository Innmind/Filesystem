<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\Name as Name_,
    File,
    File\Content,
    Name,
    Directory,
    Exception\MountPathDoesntExist,
};
use Innmind\IO\IO;
use Innmind\MediaType\MediaType;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
    Attempt,
    SideEffect,
};

final class Filesystem implements Implementation
{
    private function __construct(
        private IO $io,
        private Path $path,
    ) {
    }

    /**
     * @return Attempt<self>
     */
    public static function mount(
        Path $path,
        ?IO $io = null,
    ): Attempt {
        if (!$path->directory()) {
            return Attempt::error(new \LogicException(\sprintf(
                "Path doesn't represent a directory '%s'",
                $path->toString(),
            )));
        }

        $io ??= IO::fromAmbientAuthority();

        return self::assert($path)
            ->map($io->files()->exists(...))
            ->flatMap(static fn($exist) => match ($exist) {
                false => Attempt::error(new MountPathDoesntExist(
                    static fn() => self::assert($path)
                        ->flatMap($io->files()->create(...))
                        ->map(static fn() => new self(
                            $io,
                            $path,
                        )),
                )),
                default => Attempt::result(SideEffect::identity),
            })
            ->map(static fn() => new self(
                $io,
                $path,
            ));
    }

    #[\Override]
    public function exists(TreePath $path): Attempt
    {
        return self::assert($path->asPath($this->path))->map(
            $this->io->files()->exists(...),
        );
    }

    #[\Override]
    public function read(
        TreePath $parent,
        Name_\File|Name_\Directory|Name_\Unknown $name,
    ): Attempt {
        if ($name instanceof Name_\Directory) {
            return Attempt::result($name);
        }

        $name = $name->unwrap();
        $path = TreePath::of($name)
            ->under($parent)
            ->asPath($this->path);
        $directory = TreePath::directory($name)
            ->under($parent)
            ->asPath($this->path);

        if (Str::of($path->toString())->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        if ($this->io->files()->exists($directory)) {
            return Attempt::result(Name_\Directory::of($name));
        }

        if (!$this->io->files()->exists($path)) {
            return Attempt::error(new \RuntimeException('File not found'));
        }

        $file = File::of(
            $name,
            Content::atPath(
                $this->io,
                $path,
            ),
            $this
                ->io
                ->files()
                ->mediaType($path)
                ->maybe()
                ->flatMap(MediaType::maybe(...))
                ->match(
                    static fn($mediaType) => $mediaType,
                    static fn() => null,
                ),
        );

        return Attempt::result($file);
    }

    #[\Override]
    public function list(TreePath $parent): Sequence
    {
        return $this
            ->io
            ->files()
            ->list($parent->asPath($this->path))
            ->map(static fn($name) => match ($name->directory()) {
                true => Name_\Directory::of(Name::of($name->toString())),
                false => Name_\File::of(Name::of($name->toString())),
            });
    }

    /**
     * This method only relies on the returned boolean to know if the deletion
     * was successful or not. It doesn't check afterward if the content is no
     * longer there as it may lead to race conditions with other processes.
     *
     * Such race condition could be P1 removes a file, P2 creates the same file
     * and then P1 check the file doesn't exist. This scenario would report a
     * failure.
     *
     * This package doesn't want to bleed this global state between processes.
     * If you end up here, know that you should design your app in a way that
     * there is as little as possible race conditions like these.
     */
    #[\Override]
    public function remove(TreePath $parent, Name $name): Attempt
    {
        $path = TreePath::of($name)->under($parent);
        $absolutePath = $path->asPath($this->path);
        $asDirectory = TreePath::directory($name)
            ->under($parent)
            ->asPath($this->path);

        if (Str::of($absolutePath->toString())->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        if (!$this->io->files()->exists($absolutePath)) {
            return Attempt::result(SideEffect::identity);
        }

        if ($this->io->files()->exists($asDirectory)) {
            return $this
                ->io
                ->files()
                ->list($absolutePath)
                ->map(static fn($name) => $name->toString())
                ->map(Name::of(...))
                ->sink(SideEffect::identity)
                ->attempt(fn($_, $file) => $this->remove($path, $file))
                ->map(static fn() => @\rmdir($absolutePath->toString()))
                ->flatMap(static fn($removed) => match ($removed) {
                    true => Attempt::result(SideEffect::identity),
                    false => Attempt::error(new \RuntimeException(\sprintf(
                        "Failed to remove directory '%s'",
                        $absolutePath->toString(),
                    ))),
                });
        }

        $removed = @\unlink($absolutePath->toString());

        return match ($removed) {
            true => Attempt::result(SideEffect::identity),
            false => Attempt::error(new \RuntimeException(\sprintf(
                "Failed to remove file '%s'",
                $absolutePath->toString(),
            ))),
        };
    }

    #[\Override]
    public function createDirectory(TreePath $parent, Name $name): Attempt
    {
        $path = TreePath::directory($name)->under($parent);
        $absolutePath = $path->asPath($this->path);

        return $this
            ->exists($path)
            ->flatMap(function($exists) use ($parent, $name, $absolutePath) {
                if ($exists && \is_dir($absolutePath->toString())) {
                    return Attempt::result(SideEffect::identity);
                }

                if ($exists) {
                    return $this
                        ->remove($parent, $name)
                        ->flatMap(fn() => self::assert($absolutePath)->flatMap(
                            $this->io->files()->create(...),
                        ));
                }

                return self::assert($absolutePath)->flatMap(
                    $this->io->files()->create(...),
                );
            });
    }

    #[\Override]
    public function write(TreePath $parent, File $file): Attempt
    {
        $absolutePath = TreePath::of($file)->under($parent)->asPath($this->path);
        $chunks = $file->content()->chunks();

        return self::assert($absolutePath)
            ->flatMap($this->io->files()->create(...))
            ->flatMap(
                fn() => $this
                    ->io
                    ->files()
                    ->write($absolutePath)
                    ->watch()
                    ->sink($chunks),
            );
    }

    /**
     * @return Attempt<Path>
     */
    private static function assert(Path $path): Attempt
    {
        if (Str::of($path->toString())->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        return Attempt::result($path);
    }
}
