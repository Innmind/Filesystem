<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory as DirectoryInterface,
    Name,
    Stream,
    File,
    Exception\FileNotFoundException,
    Event\FileWasAdded,
    Event\FileWasRemoved,
    MediaType,
    MediaType\Parameter
};
use Innmind\Immutable\{
    Map,
    Str
};
use Innmind\EventBus\EventRecorder;

class Directory implements DirectoryInterface
{
    use EventRecorder;

    private $name;
    private $content;
    private $files;
    private $generator;
    private $mediaType;

    public function __construct(string $name, \Generator $generator = null)
    {
        $this->name = new Name\Name($name);
        $this->generator = $generator;
        $this->files = new Map('string', File::class);
        $this->mediaType = new MediaType\MediaType(
            'text',
            'directory',
            '',
            new Map('string', Parameter::class)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function name(): Name
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function content(): Stream
    {
        if ($this->content instanceof Stream) {
            return $this->content;
        }

        $this->loadDirectory();
        $this->content = new Stream\StringStream(
            (string) $this
                ->files
                ->keys()
                ->join("\n")
        );
        $this->rewind();

        return $this->content;
    }

    public function mediaType(): MediaType
    {
        return $this->mediaType;
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): DirectoryInterface
    {
        $this->loadDirectory();
        $directory = clone $this;
        $directory->content = null;
        $directory->files = $this->files->put(
            (string) $file->name(),
            $file
        );
        $directory->record(new FileWasAdded($file));

        return $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): File
    {
        if (!$this->has($name)) {
            throw new FileNotFoundException;
        }

        return $this->files->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        $this->loadDirectory();

        return $this->files->contains($name);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): DirectoryInterface
    {
        if (!$this->has($name)) {
            throw new FileNotFoundException;
        }

        $directory = clone $this;
        $directory->content = null;
        $directory->files = $this->files->remove($name);
        $directory->record(new FileWasRemoved($name));

        return $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceAt(string $path, File $file): DirectoryInterface
    {
        $pieces = (new Str($path))->split('/');
        $directory = $this;

        while ($pieces->count() > 0) {
            $target = $pieces
                ->reduce(
                    $directory,
                    function(DirectoryInterface $parent, Str $seek): DirectoryInterface {
                        return $parent->get((string) $seek);
                    }
                )
                ->add($target ?? $file);
            $pieces = $pieces->dropEnd(1);
        }

        return $directory->add($target);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->loadDirectory();

        return $this->files->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->current()->name();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->loadDirectory();
        $this->files->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->loadDirectory();
        $this->files->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        $this->loadDirectory();

        return $this->files->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->loadDirectory();

        return $this->files->size();
    }

    /**
     * Load all files of the directory
     *
     * @return void
     */
    private function loadDirectory()
    {
        if ($this->generator === null) {
            return;
        }

        foreach ($this->generator as $file) {
            $this->files = $this->files->put(
                (string) $file->name(),
                $file
            );
        }

        $this->generator = null;
    }
}
