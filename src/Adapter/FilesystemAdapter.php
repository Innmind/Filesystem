<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Directory,
    Stream\LazyStream,
    Exception\FileNotFound,
    Exception\PathDoesntRepresentADirectory,
    Event\FileWasAdded,
    Event\FileWasRemoved,
};
use Innmind\MediaType\{
    MediaType,
    Exception\InvalidMediaTypeString,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Set,
};
use Symfony\Component\{
    Filesystem\Filesystem,
    Finder\Finder,
};

final class FilesystemAdapter implements Adapter
{
    private const INVALID_FILES = ['.', '..'];
    private Path $path;
    private Filesystem $filesystem;
    private Map $files;
    private Set $handledEvents;

    public function __construct(Path $path)
    {
        if (!$path->directory()) {
            throw new PathDoesntRepresentADirectory($path->toString());
        }

        $this->path = $path;
        $this->filesystem = new Filesystem;
        $this->files = Map::of('string', File::class);
        $this->handledEvents = Set::objects();

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
        if (\in_array($file->toString(), self::INVALID_FILES, true)) {
            return false;
        }

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
        return Set::defer(
            File::class,
            (function(Adapter $adapter, string $path): \Generator {
                $files = Finder::create()->depth('== 0')->in($path);

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
        if ($file instanceof Directory) {
            $folder = $path->resolve(Path::of($file->name()->toString().'/'));

            if (
                $this->files->contains($folder->toString()) &&
                $this->files->get($folder->toString()) === $file
            ) {
                return;
            }

            $this->filesystem->mkdir($folder->toString());
            $file
                ->modifications()
                ->foreach(function($event) use ($folder) {
                    if ($this->handledEvents->contains($event)) {
                        return;
                    }

                    switch (true) {
                        case $event instanceof FileWasRemoved:
                            $this
                                ->filesystem
                                ->remove($folder->toString().$event->file()->toString());
                            break;
                        case $event instanceof FileWasAdded:
                            $this->createFileAt($folder, $event->file());
                            break;
                    }

                    $this->handledEvents = ($this->handledEvents)($event);
                });
            $this->files = ($this->files)($folder->toString(), $file);

            return;
        }

        $path = $path->resolve(Path::of($file->name()->toString()));

        if (
            $this->files->contains($path->toString()) &&
            $this->files->get($path->toString()) === $file
        ) {
            return;
        }

        $stream = $file->content();
        $stream->rewind();
        $handle = \fopen($path->toString(), 'w');

        while (!$stream->end()) {
            \fwrite($handle, $stream->read(8192)->toString());
        }

        $this->files = ($this->files)($path->toString(), $file);
    }

    /**
     * Open the file in the given folder
     */
    private function open(Path $folder, Name $file): File
    {
        $path = $folder->resolve(Path::of($file->toString()));

        if (\is_dir($path->toString())) {
            $object = new Directory\Directory(
                $file,
                Set::defer(File::class, (function(Path $folder) {
                    $handle = \opendir($folder->toString());

                    while (($name = \readdir($handle)) !== false) {
                        if (\in_array($name, self::INVALID_FILES, true)) {
                            continue;
                        }

                        yield $this->open($folder, new Name($name));
                    }

                    \closedir($handle);
                })($folder->resolve(Path::of($file->toString().'/')))),
            );
        } else {
            try {
                $mediaType = MediaType::of(\mime_content_type($path->toString()));
            } catch (InvalidMediaTypeString $e) {
                $mediaType = MediaType::null();
            }

            $object = new File\File(
                $file,
                new LazyStream($path),
                $mediaType,
            );
        }

        $this->files = ($this->files)($path->toString(), $object);

        return $object;
    }
}
