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
    Exception\PathTooLong,
    Exception\LinksAreNotSupported,
};
use Innmind\IO\IO;
use Innmind\MediaType\MediaType;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
    Maybe,
    Attempt,
    SideEffect,
    Set,
};
use Symfony\Component\Filesystem\Filesystem as FS;

final class Filesystem implements Adapter
{
    private const INVALID_FILES = ['.', '..'];
    private IO $io;
    private Path $path;
    private CaseSensitivity $case;
    private FS $filesystem;
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
        $this->filesystem = new FS;
        /** @var \WeakMap<File|Directory, Path> */
        $this->loaded = new \WeakMap;

        if (!$this->filesystem->exists($this->path->toString())) {
            $this->filesystem->mkdir($this->path->toString());
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

        return Maybe::just($this->open($this->path, $file));
    }

    #[\Override]
    public function contains(Name $file): bool
    {
        return $this->filesystem->exists($this->path->toString().'/'.$file->toString());
    }

    #[\Override]
    public function remove(Name $file): Attempt
    {
        return Attempt::of(
            fn() => $this->filesystem->remove(
                $this->path->toString().'/'.$file->toString(),
            ),
        )->map(static fn() => SideEffect::identity());
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

            return Attempt::of(
                fn() => $this->filesystem->mkdir($path->toString()),
            )
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
                        ->sink(null)
                        ->attempt(
                            fn($_, $file) => Attempt::of(
                                fn() => $this->filesystem->remove(
                                    $path->toString().$file->toString(),
                                ),
                            ),
                        )
                        ->map(static fn() => SideEffect::identity()),
                );
        }

        if (\is_dir($path->toString())) {
            try {
                $this->filesystem->remove($path->toString());
            } catch (\Throwable $e) {
                return Attempt::error($e);
            }
        }

        $chunks = $file->content()->chunks();

        try {
            $this->filesystem->touch($path->toString());
        } catch (\Throwable $e) {
            if (\PHP_OS === 'Darwin' && Str::of($path->toString(), Str\Encoding::ascii)->length() > 1014) {
                return Attempt::error(new PathTooLong($path->toString(), 0, $e));
            }

            return Attempt::error($e);
        }

        return $this
            ->io
            ->files()
            ->write($path)
            ->watch()
            ->sink($chunks);
    }

    /**
     * Open the file in the given folder
     */
    private function open(Path $folder, Name $file): File|Directory
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
            throw new LinksAreNotSupported($path->toString());
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
        /** @var Sequence<File> */
        return Sequence::lazy(function() use ($path): \Generator {
            $files = new \FilesystemIterator($path->toString());

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if (\is_link($file->getPathname())) {
                    throw new LinksAreNotSupported($file->getPathname());
                }

                /** @psalm-suppress ArgumentTypeCoercion */
                yield $this->open($path, Name::of($file->getBasename()));
            }
        });
    }
}
