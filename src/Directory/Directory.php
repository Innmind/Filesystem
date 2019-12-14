<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory as DirectoryInterface,
    Name,
    File,
    Exception\FileNotFound,
    Event\FileWasAdded,
    Event\FileWasRemoved,
};
use Innmind\Stream\Readable;
use Innmind\MediaType\MediaType;
use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
};

class Directory implements DirectoryInterface
{
    private Name $name;
    private ?Readable $content = null;
    private array $files;
    private ?\Generator $generator;
    private MediaType $mediaType;
    private Sequence $modifications;

    public function __construct(string $name, \Generator $generator = null)
    {
        $this->name = new Name\Name($name);
        $this->generator = $generator;
        $this->files = [];
        $this->mediaType = new MediaType(
            'text',
            'directory',
        );
        $this->modifications = Sequence::objects();
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
    public function content(): Readable
    {
        if ($this->content instanceof Readable) {
            return $this->content;
        }

        $this->loadDirectory();
        $this->content = Readable\Stream::ofContent(
            \implode("\n", \array_keys($this->files))
        );

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
        $directory->files[$file->name()->toString()] = $file;
        $directory->record(new FileWasAdded($file));

        return $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): File
    {
        if (!$this->contains($name)) {
            throw new FileNotFound($name);
        }

        return $this->files[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function contains(string $name): bool
    {
        $this->loadDirectory();

        return \array_key_exists($name, $this->files);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): DirectoryInterface
    {
        if (!$this->contains($name)) {
            return $this;;
        }

        $directory = clone $this;
        $directory->content = null;
        unset($directory->files[$name]);
        $directory->record(new FileWasRemoved($name));

        return $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceAt(string $path, File $file): DirectoryInterface
    {
        $pieces = Str::of($path)->split('/');
        $directory = $this;

        while ($pieces->count() > 0) {
            $target = $pieces
                ->reduce(
                    $directory,
                    function(DirectoryInterface $parent, Str $seek): DirectoryInterface {
                        return $parent->get($seek->toString());
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
    public function foreach(callable $function): void
    {
        $this->loadDirectory();

        foreach ($this->files as $file) {
            $function($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        $this->loadDirectory();

        foreach ($this->files as $file) {
            $carry = $reducer($carry, $file);
        }

        return $carry;
    }

    /**
     * {@inheritdoc}
     */
    public function modifications(): Sequence
    {
        return $this->modifications;
    }

    /**
     * Load all files of the directory
     */
    private function loadDirectory(): void
    {
        if ($this->generator === null) {
            return;
        }

        foreach ($this->generator as $file) {
            $this->files[$file->name()->toString()] = $file;
        }

        $this->generator = null;
    }

    private function record($event): void
    {
        $this->modifications = $this->modifications->add($event);
    }
}
