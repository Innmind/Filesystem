<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    AdapterInterface,
    FileInterface,
    File,
    Directory,
    DirectoryInterface,
    Stream\Stream,
    Exception\FileNotFoundException,
    Event\FileWasAdded,
    Event\FileWasRemoved,
    MediaType\MediaType
};
use Innmind\Immutable\{
    Map,
    Set
};
use Symfony\Component\Filesystem\Filesystem;

class FilesystemAdapter implements AdapterInterface
{
    private $path;
    private $filesystem;
    private $files;
    private $handledEvents;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->filesystem = new Filesystem;
        $this->files = new Map('string', FileInterface::class);
        $this->handledEvents = new Set('object');
    }

    /**
     * {@inheritdoc}
     */
    public function add(FileInterface $file): AdapterInterface
    {
        $this->createFileAt($this->path, $file);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $file): FileInterface
    {
        if (!$this->has($file)) {
            throw new FileNotFoundException;
        }

        return $this->open($this->path, $file);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $file): bool
    {
        return $this->filesystem->exists($this->path.'/'.$file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $file): AdapterInterface
    {
        if (!$this->has($file)) {
            throw new FileNotFoundException;
        }

        $this->filesystem->remove($this->path.'/'.$file);

        return $this;
    }

    /**
     * Create the wished file at the given absolute path
     *
     * @param string $path
     * @param FileInterface $file
     *
     * @return void
     */
    private function createFileAt(string $path, FileInterface $file)
    {
        if ($file instanceof DirectoryInterface) {
            $folder = $path.'/'.(string) $file->name();

            if (
                $this->files->contains($folder) &&
                $this->files->get($folder) === $file
            ) {
                return;
            }

            $this->filesystem->mkdir($folder);
            $file
                ->recordedEvents()
                ->foreach(function($event) use ($folder) {
                    switch (true) {
                        case $event instanceof FileWasRemoved:
                            $this
                                ->filesystem
                                ->remove($folder.'/'.$event->file());
                            break;
                        case $event instanceof FileWasAdded:
                            $this->createFileAt($folder, $event->file());
                            break;
                    }

                    $this->handledEvents = $this->handledEvents->add($event);
                });
            $this->files = $this->files->put($folder, $file);

            return;
        }

        $path .= '/'.(string) $file->name();

        if (
            $this->files->contains($path) &&
            $this->files->get($path) === $file
        ) {
            return;
        }

        $stream = $file->content();
        $stream->rewind();
        $handle = fopen($path, 'w');

        while (!$stream->isEof()) {
            fwrite($handle, $stream->read(8192));
        }

        $this->files = $this->files->put($path, $file);
    }

    /**
     * Open the file in the given folder
     *
     * @param string $folder
     * @param string $file
     *
     * @return FileInterface
     */
    private function open(string $folder, string $file): FileInterface
    {
        $path = $folder.'/'.$file;

        if ($this->files->contains($path)) {
            return $this->files->get($path);
        }

        if (is_dir($path)) {
            $object = new Directory(
                $file,
                (function($folder) {
                    $handle = opendir($folder);

                    while (($name = readdir($handle)) !== false) {
                        yield $this->open($folder, $name);
                    }

                    closedir($handle);
                })($path)
            );
        } else {
            $object = new File(
                $file,
                new Stream(
                    fopen($path, 'r')
                ),
                MediaType::fromString(mime_content_type($path))
            );
        }

        $this->files = $this->files->put($path, $object);

        return $object;
    }
}
