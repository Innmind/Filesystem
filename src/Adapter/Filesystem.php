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
    Maybe,
    Attempt,
    SideEffect,
    Set,
    Predicate\Instance,
};

final class Filesystem implements Adapter
{
    private const INVALID_FILES = ['.', '..'];
    private IO $io;
    private Path $path;
    private CaseSensitivity $case;
    /** @var \WeakMap<File|Directory, Path> */
    private \WeakMap $loaded;

    private function __construct(
        IO $io,
        Path $path,
        CaseSensitivity $case,
    ) {
        if (!$path->directory()) {
            throw new PathDoesntRepresentADirectory($path->toString());
        }

        $this->io = $io;
        $this->path = $path;
        $this->case = $case;
        /** @var \WeakMap<File|Directory, Path> */
        $this->loaded = new \WeakMap;

        if (!self::doExist($this->path->toString())->unwrap()) {
            self::mkdir($this->path->toString())->unwrap();
        }
    }

    public static function mount(
        Path $path,
        ?IO $io = null,
    ): self {
        return new self(
            $io ?? IO::fromAmbientAuthority(),
            $path,
            CaseSensitivity::sensitive,
        );
    }

    public function withCaseSensitivity(CaseSensitivity $case): self
    {
        return new self($this->io, $this->path, $case);
    }

    #[\Override]
    public function add(File|Directory $file): Attempt
    {
        return $this->createFileAt($this->path, $file);
    }

    #[\Override]
    public function get(Name $file): Maybe
    {
        if (!$this->contains($file)) {
            /** @var Maybe<File|Directory> */
            return Maybe::nothing();
        }

        return Maybe::of($this->open($this->path, $file));
    }

    #[\Override]
    public function contains(Name $file): bool
    {
        return self::doExist($this->path->toString().$file->toString())->unwrap();
    }

    #[\Override]
    public function remove(Name $file): Attempt
    {
        return self::doRemove($this->path->toString().$file->toString());
    }

    #[\Override]
    public function root(): Directory
    {
        return Directory::lazy(
            Name::of('root'),
            $this->list($this->path),
        );
    }

    /**
     * Create the wished file at the given absolute path
     *
     * @return Attempt<SideEffect>
     */
    private function createFileAt(Path $path, File|Directory $file): Attempt
    {
        $name = $file->name()->toString();

        if ($file instanceof Directory) {
            $name .= '/';
        }

        $path = $path->resolve(Path::of($name));

        /** @psalm-suppress PossiblyNullReference */
        if ($this->loaded->offsetExists($file) && $this->loaded[$file]->equals($path)) {
            // no need to persist untouched file where it was loaded from
            return Attempt::result(SideEffect::identity());
        }

        $this->loaded[$file] = $path;

        if ($file instanceof Directory) {
            /** @var Set<Name> */
            $names = Set::of();

            return self::mkdir($path->toString())
                ->flatMap(
                    fn() => $file
                        ->all()
                        ->sink($names)
                        ->attempt(
                            fn($persisted, $file) => $this
                                ->createFileAt($path, $file)
                                ->map(static fn() => ($persisted)($file->name())),
                        ),
                )
                ->flatMap(
                    fn($persisted) => $file
                        ->removed()
                        ->filter(fn($file): bool => !$this->case->contains(
                            $file,
                            $persisted,
                        ))
                        ->unsorted()
                        ->sink(SideEffect::identity)
                        ->attempt(static fn($_, $file) => self::doRemove(
                            $path->toString().$file->toString(),
                        )),
                );
        }

        return self::doRemove($path->toString())
            ->map(static fn() => $file->content()->chunks())
            ->flatMap(static fn($chunks) => self::touch($path->toString())->map(
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
     */
    private function open(Path $folder, Name $file): File|Directory|null
    {
        $path = $folder->resolve(Path::of($file->toString()));

        if (\is_dir($path->toString())) {
            $directoryPath = $folder->resolve(Path::of($file->toString().'/'));
            $files = $this->list($directoryPath);

            $directory = Directory::lazy($file, $files);
            $this->loaded[$directory] = $directoryPath;

            return $directory;
        }

        if (\is_link($path->toString())) {
            return null;
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

        return $file;
    }

    /**
     * @return Sequence<File|Directory>
     */
    private function list(Path $path): Sequence
    {
        return Sequence::lazy(function() use ($path): \Generator {
            $files = new \FilesystemIterator($path->toString());

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                /** @psalm-suppress ArgumentTypeCoercion */
                yield $this->open($path, Name::of($file->getBasename()));
            }
        })->keep(
            Instance::of(File::class)->or(
                Instance::of(Directory::class),
            ),
        );
    }

    /**
     * @return Attempt<bool>
     */
    private static function doExist(string $path): Attempt
    {
        if (Str::of($path)->length() > \PHP_MAXPATHLEN) {
            return Attempt::error(new \RuntimeException('Path too long'));
        }

        return Attempt::result(@\file_exists($path));
    }

    /**
     * @return Attempt<SideEffect>
     */
    private static function mkdir(string $path): Attempt
    {
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
    private static function touch(string $path): Attempt
    {
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
    private static function doRemove(string $path): Attempt
    {
        if (!\file_exists($path)) {
            return Attempt::result(SideEffect::identity);
        }

        if (\is_link($path)) {
            return Attempt::error(new LinksAreNotSupported);
        }

        if (\is_dir($path)) {
            $files = new \FilesystemIterator(
                $path,
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS,
            );

            return Sequence::lazy(static fn() => yield from $files)
                ->keep(Is::string()->asPredicate())
                ->sink(SideEffect::identity)
                ->attempt(static fn($_, $file) => self::doRemove($file))
                ->map(static fn() => @\rmdir($path))
                ->flatMap(static fn($removed) => match ($removed) {
                    true => Attempt::result(SideEffect::identity),
                    false => Attempt::error(new \RuntimeException(\sprintf(
                        "Failed to remove directory '%s'",
                        $path,
                    ))),
                });
        }

        $removed = @\unlink($path);

        return match ($removed) {
            true => Attempt::result(SideEffect::identity),
            false => Attempt::error(new \RuntimeException(\sprintf(
                "Failed to remove file '%s'",
                $path,
            ))),
        };
    }
}
