<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory as DirectoryInterface,
    Name,
    File,
    File\Content,
    Exception\DuplicatedFile,
};
use Innmind\MediaType\MediaType;
use Innmind\Immutable\{
    Str,
    Set,
    Maybe,
    SideEffect,
};

/**
 * @psalm-immutable
 */
final class Directory implements DirectoryInterface
{
    private Name $name;
    /** @var Set<File> */
    private Set $files;
    /** @var Set<Name> */
    private Set $removed;

    /**
     * @param Set<File> $files
     * @param Set<Name> $removed
     */
    private function __construct(Name $name, Set $files, Set $removed)
    {
        $this->name = $name;
        $this->files = $files;
        $this->removed = $removed;
    }

    /**
     * @psalm-pure
     *
     * @param Set<File>|null $files
     *
     * @throws DuplicatedFile
     */
    public static function of(Name $name, Set $files = null): self
    {
        return new self(
            $name,
            self::safeguard($files ?? Set::of()),
            Set::of(),
        );
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function named(string $name): self
    {
        return new self(
            Name::of($name),
            Set::of(),
            Set::of(),
        );
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param Set<File> $files
     */
    public static function lazy(Name $name, Set $files): self
    {
        // we prevent the contrusctor from checking for duplicates when
        // using a lazy set of files as it will trigger to load the whole
        // directory tree, it's kinda safe to do this as this method should
        // only be used within the filesystem adapter and there should be no
        // duplicates on a concrete filesystem
        return new self($name, $files, Set::of());
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function content(): Content
    {
        return Content\None::of();
    }

    public function mediaType(): MediaType
    {
        return new MediaType(
            'text',
            'directory',
        );
    }

    public function rename(Name $name): self
    {
        return new self(
            $name,
            $this->files,
            $this->removed,
        );
    }

    public function withContent(Content $content, MediaType $mediaType = null): self
    {
        return $this;
    }

    public function add(File $file): DirectoryInterface
    {
        return new self(
            $this->name,
            $this
                ->files
                ->filter(static fn(File $known): bool => !$known->name()->equals($file->name()))
                ->add($file),
            $this->removed,
        );
    }

    public function get(Name $name): Maybe
    {
        return $this->files->find(static fn($file) => $file->name()->equals($name));
    }

    public function contains(Name $name): bool
    {
        return $this->get($name)->match(
            static fn() => true,
            static fn() => false,
        );
    }

    public function remove(Name $name): DirectoryInterface
    {
        return new self(
            $this->name,
            $this->files->filter(static fn(File $file) => !$file->name()->equals($name)),
            ($this->removed)($name),
        );
    }

    public function foreach(callable $function): SideEffect
    {
        return $this->files->foreach($function);
    }

    public function filter(callable $predicate): self
    {
        // it is safe to not check for duplicates here as either the current
        // directory comes from the filesystem thus can't have duplicates or
        // comes from a user and must have used the standard constructor that
        // validates for duplicates, so they're can't be any duplicates after
        // a filter
        return self::lazy($this->name, $this->files->filter($predicate));
    }

    public function map(callable $map): self
    {
        return new self(
            $this->name,
            self::safeguard($this->files->map($map)),
            $this->removed,
        );
    }

    public function flatMap(callable $map): self
    {
        /** @var callable(File): Set<File> */
        $map = static fn(File $file): Set => $map($file)->files();

        return new self(
            $this->name,
            self::safeguard($this->files->flatMap($map)),
            $this->removed,
        );
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->files->reduce($carry, $reducer);
    }

    public function removed(): Set
    {
        return $this->removed;
    }

    public function files(): Set
    {
        return $this->files;
    }

    /**
     * @psalm-pure
     *
     * @param Set<File> $files
     *
     * @throws DuplicatedFile
     *
     * @return Set<File>
     */
    private static function safeguard(Set $files): Set
    {
        return $files->safeguard(
            Set::strings(),
            static fn(Set $names, $file) => match ($names->contains($file->name()->toString())) {
                true => throw new DuplicatedFile($file->name()),
                false => ($names)($file->name()->toString()),
            },
        );
    }
}
