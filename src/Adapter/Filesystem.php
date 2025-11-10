<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Directory,
    CaseSensitivity,
    Exception\PathDoesntRepresentADirectory,
    Exception\LinksAreNotSupported,
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
    Set,
};

final class Filesystem implements Implementation
{
    /** @var \WeakMap<File|Directory, Path> */
    private \WeakMap $loaded;

    private function __construct(
        private IO $io,
        private Path $path,
        private CaseSensitivity $case,
    ) {
        /** @var \WeakMap<File|Directory, Path> */
        $this->loaded = new \WeakMap;
    }

    /**
     * @return Attempt<Adapter>
     */
    public static function mount(
        Path $path,
        CaseSensitivity $case = CaseSensitivity::sensitive,
        ?IO $io = null,
    ): Attempt {
        if (!$path->directory()) {
            return Attempt::error(new PathDoesntRepresentADirectory($path->toString()));
        }

        return self::doExist($path)
            ->flatMap(static fn($exist) => match ($exist) {
                false => self::mkdir($path),
                default => Attempt::result(SideEffect::identity),
            })
            ->map(static fn() => new self(
                $io ?? IO::fromAmbientAuthority(),
                $path,
                $case,
            ))
            ->map(Bridge::of(...));
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function add(File|Directory $file): Attempt
    {
        return $this->createFileAt(TreePath::root(), $file);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(Name $file): Attempt
    {
        return $this->doRemove(TreePath::of($file));
    }

    #[\Override]
    public function exists(TreePath $path): Attempt
    {
        return self::doExist($path->asPath($this->path));
    }

    #[\Override]
    public function read(TreePath $path): Attempt
    {
        return $this
            ->exists($path)
            ->flatMap(static fn($exists) => match ($exists) {
                true => Attempt::result(true),
                false => Attempt::error(new \RuntimeException('File not found')),
            })
            ->flatMap(
                fn() => $path->match(
                    fn($name, $parent) => $this->open($parent, $name),
                    static fn() => Attempt::error(new \RuntimeException('Root folder is not accessible')),
                ),
            );
    }

    #[\Override]
    public function list(TreePath $parent): Sequence
    {
        return Sequence::lazy(function() use ($parent): \Generator {
            $files = new \FilesystemIterator($parent->asPath($this->path)->toString());

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                /** @psalm-suppress ArgumentTypeCoercion */
                yield TreePath::of(Name::of($file->getBasename()));
            }
        });
    }

    /**
     * Create the wished file at the given absolute path
     *
     * @return Attempt<SideEffect>
     */
    private function createFileAt(TreePath $parent, File|Directory $file): Attempt
    {
        $path = TreePath::of($file)
            ->under($parent)
            ->asPath($this->path);

        /** @psalm-suppress PossiblyNullReference */
        if ($this->loaded->offsetExists($file) && $this->loaded[$file]->equals($path)) {
            // no need to persist untouched file where it was loaded from
            return Attempt::result(SideEffect::identity());
        }

        $this->loaded[$file] = $path;

        if ($file instanceof Directory) {
            /** @var Set<Name> */
            $names = Set::of();
            $parent = TreePath::of($file)->under($parent);

            return self::mkdir($path)
                ->flatMap(
                    fn() => $file
                        ->all()
                        ->sink($names)
                        ->attempt(
                            fn($persisted, $file) => $this
                                ->createFileAt($parent, $file)
                                ->map(static fn() => ($persisted)($file->name())),
                        ),
                )
                ->flatMap(
                    fn($persisted) => $file
                        ->removed()
                        ->exclude(fn($file): bool => $this->case->contains(
                            $file,
                            $persisted,
                        ))
                        ->unsorted()
                        ->map(TreePath::of(...))
                        ->map(static fn($file) => $file->under($parent))
                        ->sink(SideEffect::identity)
                        ->attempt(fn($_, $file) => $this->doRemove($file)),
                );
        }

        return $this
            ->doRemove(TreePath::of($file)->under($parent))
            ->map(static fn() => $file->content()->chunks())
            ->flatMap(static fn($chunks) => self::touch($path)->map(
                static fn() => $chunks,
            ))
            ->flatMap(
                fn($chunks) => $this
                    ->io
                    ->files()
                    ->write($path)
                    ->watch()
                    ->sink($chunks),
            );
    }

    /**
     * Open the file in the given folder
     *
     * @return Attempt<File|Name> A Name represent a directory
     */
    private function open(TreePath $parent, Name $file): Attempt
    {
        $path = TreePath::of($file)
            ->under($parent)
            ->asPath($this->path);

        if (Str::of($path->toString())->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        if (!\file_exists($path->toString())) {
            return Attempt::error(new \RuntimeException('File not found'));
        }

        if (\is_dir($path->toString())) {
            return Attempt::result($file);
        }

        if (\is_link($path->toString())) {
            return Attempt::error(new LinksAreNotSupported);
        }

        $file = File::of(
            $file,
            File\Content::atPath(
                $this->io,
                $path,
            ),
            MediaType::maybe(match ($mediaType = @\mime_content_type($path->toString())) {
                false => '',
                default => $mediaType,
            })->match(
                static fn($mediaType) => $mediaType,
                static fn() => MediaType::null(),
            ),
        );
        $this->loaded[$file] = $path;

        return Attempt::result($file);
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
     *
     * @return Attempt<SideEffect>
     */
    private function doRemove(TreePath $path): Attempt
    {
        $absolutePath = $path->asPath($this->path)->toString();

        if (Str::of($absolutePath)->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        if (!\file_exists($absolutePath)) {
            return Attempt::result(SideEffect::identity);
        }

        if (\is_link($absolutePath)) {
            return Attempt::error(new LinksAreNotSupported);
        }

        if (\is_dir($absolutePath)) {
            $files = new \FilesystemIterator($absolutePath);

            return Sequence::lazy(static fn() => yield from $files)
                ->map(static fn($file) => $file->getBasename())
                ->keep(Is::string()->nonEmpty()->asPredicate())
                ->map(Name::of(...))
                ->map(TreePath::of(...))
                ->map(static fn($file) => $file->under($path))
                ->sink(SideEffect::identity)
                ->attempt(fn($_, $file) => $this->doRemove($file))
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
