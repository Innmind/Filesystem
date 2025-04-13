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
    Exception\RuntimeException,
    Exception\LinksAreNotSupported,
};
use Innmind\IO\IO;
use Innmind\MediaType\MediaType;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
    Maybe,
};
use Symfony\Component\{
    Filesystem\Filesystem as FS,
    Filesystem\Exception\IOException,
};

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

    public function add(File|Directory $file): void
    {
        $this->createFileAt($this->path, $file);
    }

    public function get(Name $file): Maybe
    {
        if (!$this->contains($file)) {
            /** @var Maybe<File|Directory> */
            return Maybe::nothing();
        }

        return Maybe::just($this->open($this->path, $file));
    }

    public function contains(Name $file): bool
    {
        return $this->filesystem->exists($this->path->toString().'/'.$file->toString());
    }

    public function remove(Name $file): void
    {
        $this->filesystem->remove($this->path->toString().'/'.$file->toString());
    }

    public function root(): Directory
    {
        return Directory::lazy(
            Name::of('root'),
            $this->list($this->path),
        );
    }

    /**
     * Create the wished file at the given absolute path
     */
    private function createFileAt(Path $path, File|Directory $file): void
    {
        $name = $file->name()->toString();

        if ($file instanceof Directory) {
            $name .= '/';
        }

        $path = $path->resolve(Path::of($name));

        if ($this->loaded->offsetExists($file) && $this->loaded[$file]->equals($path)) {
            // no need to persist untouched file where it was loaded from
            return;
        }

        $this->loaded[$file] = $path;

        if ($file instanceof Directory) {
            $this->filesystem->mkdir($path->toString());
            $persisted = $file
                ->all()
                ->map(function($file) use ($path) {
                    $this->createFileAt($path, $file);

                    return $file;
                })
                ->map(static fn($file) => $file->name())
                ->memoize()
                ->toSet();
            /**
             * @psalm-suppress MissingClosureReturnType
             */
            $_ = $file
                ->removed()
                ->filter(fn($file): bool => !$this->case->contains($file, $persisted))
                ->foreach(fn($file) => $this->filesystem->remove(
                    $path->toString().$file->toString(),
                ));

            return;
        }

        if (\is_dir($path->toString())) {
            $this->filesystem->remove($path->toString());
        }

        $chunks = $file->content()->chunks();

        try {
            $this->filesystem->touch($path->toString());
        } catch (IOException $e) {
            if (\PHP_OS === 'Darwin' && Str::of($path->toString(), Str\Encoding::ascii)->length() > 1014) {
                throw new PathTooLong($path->toString(), 0, $e);
            }

            throw new RuntimeException(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }

        $_ = $this
            ->io
            ->files()
            ->write($path)
            ->sink($chunks)
            ->unwrap();
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
