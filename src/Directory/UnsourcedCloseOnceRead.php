<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory,
    Name,
    File,
    Adapter,
    Source,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable;
use Innmind\MediaType\MediaType;
use Innmind\Immutable\{
    Sequence,
    Set,
};

final class UnsourcedCloseOnceRead implements Directory
{
    private Directory $directory;

    public function __construct(Directory $directory)
    {
        $this->directory = $directory;
    }

    public function name(): Name
    {
        return $this->directory->name();
    }

    public function content(): Readable
    {
        return $this->directory->content();
    }

    public function mediaType(): MediaType
    {
        return $this->directory->mediaType();
    }

    public function add(File $file): Directory
    {
        // todo return an UnsourcedCloseOnceRead object
        return new self($this->directory->add($file));
    }

    public function get(Name $name): File
    {
        return $this->wrap($this->directory->get($name));
    }

    public function contains(Name $name): bool
    {
        return $this->directory->contains($name);
    }

    public function remove(Name $name): Directory
    {
        $directory = $this->directory->remove($name);

        if ($directory === $this->directory) {
            return $this;
        }

        // todo return an UnsourcedCloseOnceRead object
        return new self($directory);
    }

    public function replaceAt(Path $path, File $file): Directory
    {
        return new self($this->directory->replaceAt($path, $file));
    }

    public function foreach(callable $function): void
    {
        $this->directory->foreach(function(File $file) use ($function): void {
            $function($this->wrap($file));
        });
    }

    public function filter(callable $predicate): Set
    {
        return $this->directory->filter(function(File $file) use ($predicate): bool {
            return $predicate($this->wrap($file));
        });
    }

    public function reduce($carry, callable $reducer)
    {
        /** @psalm-suppress MissingClosureParamType */
        return $this->directory->reduce(
            $carry,
            function($carry, File $file) use ($reducer) {
                /** @psalm-suppress MixedArgument */
                return $reducer($carry, $this->wrap($file));
            },
        );
    }

    public function modifications(): Sequence
    {
        return $this->directory->modifications();
    }

    private function wrap(File $file): File
    {
        if ($file instanceof Directory) {
            return new self($file);
        }

        return new File\CloseOnceRead($file);
    }
}
