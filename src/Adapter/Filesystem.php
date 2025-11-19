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
use Innmind\Validation\Is;
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

        return self::doExist($path)
            ->flatMap(static fn($exist) => match ($exist) {
                false => Attempt::error(new MountPathDoesntExist(
                    static fn() => self::mkdir($path)->map(static fn() => new self(
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
        return self::doExist($path->asPath($this->path));
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

        if (Str::of($path->toString())->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        if (!\file_exists($path->toString())) {
            return Attempt::error(new \RuntimeException('File not found'));
        }

        if (\is_dir($path->toString())) {
            return Attempt::result(Name_\Directory::of($name));
        }

        if (\is_link($path->toString())) {
            return Attempt::error(new \RuntimeException('Links are not supported'));
        }

        $file = File::of(
            $name,
            Content::atPath(
                $this->io,
                $path,
            ),
            MediaType::maybe(match ($mediaType = @\mime_content_type($path->toString())) {
                false => '',
                default => $mediaType,
            })->match(
                static fn($mediaType) => $mediaType,
                static fn() => null,
            ),
        );

        return Attempt::result($file);
    }

    #[\Override]
    public function list(TreePath $parent): Sequence
    {
        return Sequence::lazy(function() use ($parent): \Generator {
            $files = new \FilesystemIterator($parent->asPath($this->path)->toString());

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                /** @psalm-suppress ArgumentTypeCoercion */
                $name = Name::of($file->getBasename());

                yield match ($file->isDir()) {
                    true => Name_\Directory::of($name),
                    false => Name_\File::of($name),
                };
            }
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
        $absolutePath = $path->asPath($this->path)->toString();

        if (Str::of($absolutePath)->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        if (!\file_exists($absolutePath)) {
            return Attempt::result(SideEffect::identity);
        }

        if (\is_link($absolutePath)) {
            return Attempt::error(new \RuntimeException('Links are not supported'));
        }

        if (\is_dir($absolutePath)) {
            $files = new \FilesystemIterator($absolutePath);

            return Sequence::lazy(static fn() => yield from $files)
                ->map(static fn($file) => $file->getBasename())
                ->keep(Is::string()->nonEmpty()->asPredicate())
                ->map(Name::of(...))
                ->sink(SideEffect::identity)
                ->attempt(fn($_, $file) => $this->remove($path, $file))
                ->map(static fn() => @\rmdir($absolutePath))
                ->flatMap(static fn($removed) => match ($removed) {
                    true => Attempt::result(SideEffect::identity),
                    false => Attempt::error(new \RuntimeException(\sprintf(
                        "Failed to remove directory '%s'",
                        $absolutePath,
                    ))),
                });
        }

        $removed = @\unlink($absolutePath);

        return match ($removed) {
            true => Attempt::result(SideEffect::identity),
            false => Attempt::error(new \RuntimeException(\sprintf(
                "Failed to remove file '%s'",
                $absolutePath,
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
                        ->flatMap(static fn() => self::mkdir($absolutePath));
                }

                return self::mkdir($absolutePath);
            });
    }

    #[\Override]
    public function write(TreePath $parent, File $file): Attempt
    {
        $absolutePath = TreePath::of($file)->under($parent)->asPath($this->path);
        $chunks = $file->content()->chunks();

        return self::touch($absolutePath)->flatMap(
            fn() => $this
                ->io
                ->files()
                ->write($absolutePath)
                ->watch()
                ->sink($chunks),
        );
    }

    /**
     * @return Attempt<bool>
     */
    private static function doExist(Path $path): Attempt
    {
        $path = $path->toString();

        if (Str::of($path)->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        return Attempt::result(@\file_exists($path));
    }

    /**
     * @return Attempt<SideEffect>
     */
    private static function mkdir(Path $path): Attempt
    {
        $path = $path->toString();

        if (Str::of($path)->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        // We do not check the result of this function as it will return false
        // if the path already exist. This can lead to race conditions where
        // another process created the directory between the condition that
        // checked if it existed and the call to this method. The only important
        // part is to check wether the directory exists or not afterward.
        @\mkdir($path, recursive: true);

        if (!\is_dir($path)) {
            return Attempt::error(new \RuntimeException(\sprintf(
                "Failed to create directory '%s'",
                $path,
            )));
        }

        return Attempt::result(SideEffect::identity);
    }

    /**
     * @return Attempt<SideEffect>
     */
    private static function touch(Path $path): Attempt
    {
        $path = $path->toString();

        if (Str::of($path)->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        if (!@\touch($path)) {
            return Attempt::error(new \RuntimeException(\sprintf(
                "Failed to create file '%s'",
                $path,
            )));
        }

        if (!\file_exists($path)) {
            return Attempt::error(new \RuntimeException(\sprintf(
                "Failed to create file '%s'",
                $path,
            )));
        }

        return Attempt::result(SideEffect::identity);
    }
}
