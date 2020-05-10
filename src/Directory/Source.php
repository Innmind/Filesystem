<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory,
    Source as SourceInterface,
    Name,
    File,
    Adapter,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable;
use Innmind\MediaType\MediaType;
use Innmind\Immutable\{
    Sequence,
    Set,
};

final class Source implements Directory, SourceInterface
{
    private Directory $directory;
    private Adapter $openedBy;
    private Path $path;

    public function __construct(
        Directory $directory,
        Adapter $openedBy,
        Path $path
    ) {
        $this->directory = $directory;
        $this->openedBy = $openedBy;
        $this->path = $path;
    }

    public function sourcedAt(Adapter $adapter, Path $path): bool
    {
        return $this->openedBy === $adapter &&
            $this->path->equals($path);
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
        return $this->directory->add($file);
    }

    public function get(Name $name): File
    {
        return $this->directory->get($name);
    }

    public function contains(Name $name): bool
    {
        return $this->directory->contains($name);
    }

    public function remove(Name $name): Directory
    {
        if (!$this->contains($name)) {
            return $this;
        }

        return $this->directory->remove($name);
    }

    public function replaceAt(Path $path, File $file): Directory
    {
        return $this->directory->replaceAt($path, $file);
    }

    public function foreach(callable $function): void
    {
        $this->directory->foreach($function);
    }

    public function filter(callable $predicate): Set
    {
        return $this->directory->filter($predicate);
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->directory->reduce($carry, $reducer);
    }

    public function modifications(): Sequence
    {
        return $this->directory->modifications();
    }
}
