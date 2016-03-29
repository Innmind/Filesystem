<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\{
    Stream\StringStream,
    Exception\FileNotFoundException
};
use Innmind\Immutable\Map;

class Directory implements DirectoryInterface
{
    private $name;
    private $content;
    private $files;
    private $generator;

    public function __construct(string $name, \Generator $generator= null)
    {
        $this->name = new Name($name);
        $this->generator = $generator;
        $this->files = new Map('string', FileInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function name(): NameInterface
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function content(): StreamInterface
    {
        if ($this->content instanceof StreamInterface) {
            return $this->content;
        }

        $this->loadDirectory();
        $this->content = new StringStream(
            (string) $this
                ->files
                ->keys()
                ->join("\n")
        );
        $this->rewind();

        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function add(FileInterface $file): DirectoryInterface
    {
        $this->loadDirectory();
        $directory = clone $this;
        $directory->content = null;
        $directory->files = $this->files->put(
            (string) $file->name(),
            $file
        );

        return $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): FileInterface
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
        $this->loadDirectory();
        $directory = clone $this;
        $directory->content = null;
        $directory->files = $this->files->remove($name);

        return $directory;
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
