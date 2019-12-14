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
    Exception\InvalidMediaTypeString,
    Event\FileWasAdded,
    Event\FileWasRemoved,
};
use Innmind\MediaType\MediaType;
use Innmind\Immutable\{
    Map,
    Set,
};
use Symfony\Component\{
    Filesystem\Filesystem,
    Finder\Finder,
};

class FilesystemAdapter implements Adapter
{
    private const INVALID_FILES = ['.', '..'];
    private string $path;
    private Filesystem $filesystem;
    private Map $files;
    private Set $handledEvents;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->filesystem = new Filesystem;
        $this->files = Map::of('string', File::class);
        $this->handledEvents = Set::objects();

        if (!$this->filesystem->exists($this->path)) {
            $this->filesystem->mkdir($this->path);
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

        return $this->open($this->path, $file->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function contains(Name $file): bool
    {
        if (\in_array($file->toString(), self::INVALID_FILES, true)) {
            return false;
        }

        return $this->filesystem->exists($this->path.'/'.$file->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $file): void
    {
        $this->filesystem->remove($this->path.'/'.$file->toString());
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
                    yield $adapter->get(new Name\Name($file->getRelativePathname()));
                }
            })($this, $this->path),
        );
    }

    /**
     * Create the wished file at the given absolute path
     */
    private function createFileAt(string $path, File $file): void
    {
        if ($file instanceof Directory) {
            $folder = $path.'/'.$file->name()->toString();

            if (
                $this->files->contains($folder) &&
                $this->files->get($folder) === $file
            ) {
                return;
            }

            $this->filesystem->mkdir($folder);
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
                                ->remove($folder.'/'.$event->file()->toString());
                            break;
                        case $event instanceof FileWasAdded:
                            $this->createFileAt($folder, $event->file());
                            break;
                    }

                    $this->handledEvents = ($this->handledEvents)($event);
                });
            $this->files = ($this->files)($folder, $file);

            return;
        }

        $path .= '/'.$file->name()->toString();

        if (
            $this->files->contains($path) &&
            $this->files->get($path) === $file
        ) {
            return;
        }

        $stream = $file->content();
        $stream->rewind();
        $handle = \fopen($path, 'w');

        while (!$stream->end()) {
            \fwrite($handle, $stream->read(8192)->toString());
        }

        $this->files = ($this->files)($path, $file);
    }

    /**
     * Open the file in the given folder
     */
    private function open(string $folder, string $file): File
    {
        $path = $folder.'/'.$file;

        if (\is_dir($path)) {
            $object = new Directory\Directory(
                $file,
                Set::defer(File::class, (function($folder) {
                    $handle = \opendir($folder);

                    while (($name = \readdir($handle)) !== false) {
                        if (\in_array($name, self::INVALID_FILES, true)) {
                            continue;
                        }

                        yield $this->open($folder, $name);
                    }

                    \closedir($handle);
                })($path)),
            );
        } else {
            try {
                $mediaType = MediaType::of(\mime_content_type($path));
            } catch (InvalidMediaTypeString $e) {
                $mediaType = MediaType::null();
            }

            $object = new File\File(
                $file,
                new LazyStream($path),
                $mediaType,
            );
        }

        $this->files = ($this->files)($path, $object);

        return $object;
    }
}
