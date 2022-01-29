<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Directory,
    Exception\PathDoesntRepresentADirectory,
    Exception\PathTooLong,
    Exception\RuntimeException,
    Exception\CannotPersistClosedStream,
    Exception\LinksAreNotSupported,
    Exception\FailedToWriteFile,
};
use Innmind\Stream\{
    Writable,
    Writable\Stream,
};
use Innmind\MediaType\MediaType;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Set,
    Str,
    Maybe,
    Either,
};
use Symfony\Component\{
    Filesystem\Filesystem as FS,
    Filesystem\Exception\IOException,
    Finder\Finder,
};

final class Filesystem implements Adapter
{
    private const INVALID_FILES = ['.', '..'];
    private Path $path;
    private FS $filesystem;
    private Chunk $chunk;
    /** @var \WeakMap<File, Path> */
    private \WeakMap $loaded;

    public function __construct(Path $path)
    {
        if (!$path->directory()) {
            throw new PathDoesntRepresentADirectory($path->toString());
        }

        $this->path = $path;
        $this->filesystem = new FS;
        $this->chunk = new Chunk;
        /** @var \WeakMap<File, Path> */
        $this->loaded = new \WeakMap;

        if (!$this->filesystem->exists($this->path->toString())) {
            $this->filesystem->mkdir($this->path->toString());
        }
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
        return $this->list($this->path);
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
            $persisted = $file->reduce(
                Set::strings(),
                function(Set $persisted, File $file) use ($path): Set {
                    $this->createFileAt($path, $file);

                    return ($persisted)($file->name()->toString());
                },
            );
            /**
             * @psalm-suppress MissingClosureReturnType
             */
            $_ = $file
                ->removed()
                ->filter(static fn($file): bool => !$persisted->contains($file->toString()))
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

        $handle = Stream::of(\fopen($path->toString(), 'w'));

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
            ->leftMap(static fn() => throw new FailedToWriteFile);
    }

    /**
     * Open the file in the given folder
     */
    private function open(Path $folder, Name $file): File
    {
        $path = $folder->resolve(Path::of($file->toString()));

        if (\is_dir($path->toString())) {
            $files = $this->list($folder->resolve(Path::of($file->toString().'/')));

            return Directory\Directory::defer($file, $files);
        }

        if (\is_link($path->toString())) {
            throw new LinksAreNotSupported($path->toString());
        }

        $file = new File\File(
            $file,
            File\Content\AtPath::of($path),
            MediaType::maybe(\mime_content_type($path->toString()))->match(
                static fn($mediaType) => $mediaType,
                static fn() => MediaType::null(),
            ),
        );
        $this->loaded[$file] = $path;

        return $file;
    }

    /**
     * @return Set<File>
     */
    private function list(Path $path): Set
    {
        /** @var Set<File> */
        return Set::defer((function(Path $folder): \Generator {
            $files = new \FilesystemIterator($folder->toString());

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if (\is_link($file->getPathname())) {
                    throw new LinksAreNotSupported($file->getPathname());
                }

                yield $this->open($folder, new Name($file->getBasename()));
            }
        })($path));
    }
}
