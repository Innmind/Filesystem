<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Directory,
    Chunk,
    CaseSensitivity,
    Exception\PathDoesntRepresentADirectory,
    Exception\PathTooLong,
    Exception\RuntimeException,
    Exception\CannotPersistClosedStream,
    Exception\LinksAreNotSupported,
    Exception\FailedToWriteFile,
};
use Innmind\Stream\{
    Capabilities,
    Streams,
    Writable,
};
use Innmind\MediaType\MediaType;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Set,
    Sequence,
    Str,
    Maybe,
    Either,
};
use Symfony\Component\{
    Filesystem\Filesystem as FS,
    Filesystem\Exception\IOException,
};

final class Filesystem implements Adapter
{
    private const INVALID_FILES = ['.', '..'];
    private Capabilities $capabilities;
    private Path $path;
    private CaseSensitivity $case;
    private FS $filesystem;
    private Chunk $chunk;
    /** @var \WeakMap<File, Path> */
    private \WeakMap $loaded;

    private function __construct(
        Capabilities $capabilities,
        Path $path,
        CaseSensitivity $case,
    ) {
        if (!$path->directory()) {
            throw new PathDoesntRepresentADirectory($path->toString());
        }

        $this->capabilities = $capabilities;
        $this->path = $path;
        $this->case = $case;
        $this->filesystem = new FS;
        $this->chunk = new Chunk;
        /** @var \WeakMap<File, Path> */
        $this->loaded = new \WeakMap;

        if (!$this->filesystem->exists($this->path->toString())) {
            $this->filesystem->mkdir($this->path->toString());
        }
    }

    public static function mount(Path $path, Capabilities $capabilities = null): self
    {
        return new self(
            $capabilities ?? Streams::of(),
            $path,
            CaseSensitivity::sensitive,
        );
    }

    public function withCaseSensitivity(CaseSensitivity $case): self
    {
        return new self($this->capabilities, $this->path, $case);
    }

    public function add(File $file): void
    {
        $this->createFileAt($this->path, $file);
    }

    public function get(Name $file): Maybe
    {
        if (!$this->contains($file)) {
            /** @var Maybe<File> */
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

    public function all(): Set
    {
        return Set::of(...$this->root()->files()->toList());
    }

    public function root(): Directory
    {
        return Directory\Directory::lazy(
            Name::of('root'),
            $this->list($this->path),
        );
    }

    /**
     * Create the wished file at the given absolute path
     */
    private function createFileAt(Path $path, File $file): void
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
            /** @var Set<Name> */
            $persisted = $file->reduce(
                Set::of(),
                function(Set $persisted, File $file) use ($path): Set {
                    $this->createFileAt($path, $file);

                    return ($persisted)($file->name());
                },
            );
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

        $chunks = ($this->chunk)($file->content());

        try {
            $this->filesystem->touch($path->toString());
        } catch (IOException $e) {
            if (\PHP_OS === 'Darwin' && Str::of($path->toString(), 'ASCII')->length() > 1014) {
                throw new PathTooLong($path->toString(), 0, $e);
            }

            throw new RuntimeException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e,
            );
        }

        $handle = $this->capabilities->writable()->open($path);

        $_ = $chunks
            ->reduce(
                $handle,
                static fn(Writable $handle, Str $chunk): Writable => $handle
                    ->write($chunk->toEncoding('ASCII'))
                    ->match(
                        static fn($handle) => $handle,
                        static fn() => throw new FailedToWriteFile,
                    ),
            )
            ->close()
            ->match(
                static fn() => null,
                static fn() => throw new FailedToWriteFile,
            );
    }

    /**
     * Open the file in the given folder
     */
    private function open(Path $folder, Name $file): File
    {
        $path = $folder->resolve(Path::of($file->toString()));

        if (\is_dir($path->toString())) {
            $files = $this->list($folder->resolve(Path::of($file->toString().'/')));

            return Directory\Directory::lazy($file, $files);
        }

        if (\is_link($path->toString())) {
            throw new LinksAreNotSupported($path->toString());
        }

        $file = File\File::of(
            $file,
            File\Content\AtPath::of($path, $this->capabilities->readable()),
            MediaType::maybe(\mime_content_type($path->toString()))->match(
                static fn($mediaType) => $mediaType,
                static fn() => MediaType::null(),
            ),
        );
        $this->loaded[$file] = $path;

        return $file;
    }

    /**
     * @return Sequence<File>
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
