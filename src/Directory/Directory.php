<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory as DirectoryInterface,
    Name,
    File,
    File\Content,
    Exception\LogicException,
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
     * @param Set<File>|null $files
     */
    private function __construct(Name $name, Set $files = null)
    {
        /** @var Set<File> $default */
        $default = Set::of();

        $this->name = $name;
        $this->files = $files ?? $default;
        /** @var Set<Name> */
        $this->removed = Set::of();
    }

    /**
     * @psalm-pure
     *
     * @param Set<File>|null $files
     */
    public static function of(Name $name, Set $files = null): self
    {
        return new self($name, $files?->safeguard(
            Set::strings(),
            static fn(Set $names, $file) => match ($names->contains($file->name()->toString())) {
                true => throw new LogicException("Same file '{$file->name()->toString()}' found multiple times"),
                false => ($names)($file->name()->toString()),
            },
        ));
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function named(string $name): self
    {
        return new self(Name::of($name));
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
        return new self($name, $files);
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

    public function withContent(Content $content, MediaType $mediaType = null): self
    {
        return $this;
    }

    public function add(File $file): DirectoryInterface
    {
        $files = $this->files->filter(static fn(File $known): bool => !$known->name()->equals($file->name()));

        $directory = clone $this;
        $directory->files = ($files)($file);

        return $directory;
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
        $directory = clone $this;
        $directory->files = $this->files->filter(static fn(File $file) => !$file->name()->equals($name));
        $directory->removed = ($directory->removed)($name);

        return $directory;
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

    public function reduce($carry, callable $reducer)
    {
        return $this->files->reduce($carry, $reducer);
    }

    public function removed(): Set
    {
        return $this->removed;
    }
}
