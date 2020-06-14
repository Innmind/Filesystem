<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Directory,
    Stream\LazyStream,
    Source,
    Exception\FileNotFound,
    Exception\PathDoesntRepresentADirectory,
    Exception\PathTooLong,
    Exception\RuntimeException,
    Exception\CannotPersistClosedStream,
    Event\FileWasRemoved,
};
use Innmind\Stream\Writable\Stream;
use Innmind\MediaType\{
    MediaType,
    Exception\InvalidMediaTypeString,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Set,
    Str,
};
use Symfony\Component\{
    Filesystem\Filesystem as FS,
    Filesystem\Exception\IOException,
    Finder\Finder,
    Finder\SplFileInfo,
};

final class Filesystem implements Adapter
{
    private const INVALID_FILES = ['.', '..'];
    private Path $path;
    private FS $filesystem;

    public function __construct(Path $path)
    {
        if (!$path->directory()) {
            throw new PathDoesntRepresentADirectory($path->toString());
        }

        $this->path = $path;
        $this->filesystem = new FS;

        if (!$this->filesystem->exists($this->path->toString())) {
            $this->filesystem->mkdir($this->path->toString());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): void
    {
        $this->createFileAt($this->path, $file);
    }

    /**
     * {@inheritdoc}
     */
    public function get(Name $file): File
    {
        if (!$this->contains($file)) {
            throw new FileNotFound($file->toString());
        }

        return $this->open($this->path, $file);
    }

    /**
     * {@inheritdoc}
     */
    public function contains(Name $file): bool
    {
        return $this->filesystem->exists($this->path->toString().'/'.$file->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $file): void
    {
        $this->filesystem->remove($this->path->toString().'/'.$file->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Set
    {
        /** @var Set<File> */
        return Set::defer(
            File::class,
            (function(Adapter $adapter, string $path): \Generator {
                $files = Finder::create()->depth('== 0')->in($path);

                /** @var SplFileInfo $file */
                foreach ($files as $file) {
                    yield $adapter->get(new Name($file->getRelativePathname()));
                }
            })($this, $this->path->toString()),
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

        if ($file instanceof Source && $file->sourcedAt($this, $path)) {
            // no need to persist untouched file where it was loaded from
            return;
        }

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
             * @psalm-suppress ArgumentTypeCoercion
             * @psalm-suppress MissingClosureReturnType
             */
            $file
                ->modifications()
                ->filter(static fn(object $event): bool => $event instanceof FileWasRemoved)
                ->filter(static fn(FileWasRemoved $event): bool => !$persisted->contains($event->file()->toString()))
                ->foreach(fn(FileWasRemoved $event) => $this->filesystem->remove(
                    $path->toString().$event->file()->toString(),
                ));

            return;
        }

        $stream = $file->content();

        if ($stream->closed()) {
            throw new CannotPersistClosedStream($path->toString());
        }

        $stream->rewind();

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

        $handle = new Stream(\fopen($path->toString(), 'w'));

        while (!$stream->end()) {
            $handle->write(
                $stream->read(8192)->toEncoding('ASCII'),
            );
        }
    }

    /**
     * Open the file in the given folder
     */
    private function open(Path $folder, Name $file): File
    {
        $path = $folder->resolve(Path::of($file->toString()));

        if (\is_dir($path->toString())) {
            /** @var Set<File> $files */
            $files = Set::defer(File::class, (function(Path $folder): \Generator {
                $handle = \opendir($folder->toString());

                while (($name = \readdir($handle)) !== false) {
                    if (\in_array($name, self::INVALID_FILES, true)) {
                        continue;
                    }

                    yield $this->open($folder, new Name($name));
                }

                \closedir($handle);
            })($folder->resolve(Path::of($file->toString().'/'))));

            return new Directory\Source(
                new Directory\Directory($file, $files),
                $this,
                $path,
            );
        }

        try {
            $mediaType = MediaType::of(\mime_content_type($path->toString()));
        } catch (InvalidMediaTypeString $e) {
            $mediaType = MediaType::null();
        }

        return new File\Source(
            new File\File(
                $file,
                new LazyStream($path),
                $mediaType,
            ),
            $this,
            $path,
        );
    }
}
