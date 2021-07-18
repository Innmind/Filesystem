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
};

final class Directory implements DirectoryInterface
{
    private Name $name;
    /** @var Set<File> */
    private Set $files;
    private MediaType $mediaType;
    /** @var Set<Name> */
    private Set $removed;

    /**
     * @param Set<File>|null $files
     */
    private function __construct(Name $name, Set $files = null, bool $validate = true)
    {
        /** @var Set<File> $default */
        $default = Set::of();
        $files ??= $default;

        if ($validate) {
            $_ = $files->reduce(
                Set::strings(),
                static function(Set $names, File $file): Set {
                    $name = $file->name()->toString();

                    if ($names->contains($name)) {
                        throw new LogicException("Same file '$name' found multiple times");
                    }

                    return ($names)($name);
                },
            );
        }

        $this->name = $name;
        $this->files = $files;
        $this->mediaType = new MediaType(
            'text',
            'directory',
        );
        /** @var Set<Name> */
        $this->removed = Set::of();
    }

    /**
     * @param Set<File>|null $files
     */
    public static function of(Name $name, Set $files = null): self
    {
        return new self($name, $files);
    }

    public static function named(string $name): self
    {
        return new self(new Name($name));
    }

    /**
     * @internal
     *
     * @param Set<File> $files
     */
    public static function defer(Name $name, Set $files): self
    {
        // we prevent the contrusctor from checking for duplicates when
        // using a deferred set of files as it will trigger to load the whole
        // directory tree, it's kinda safe to do this as this method should
        // only be used within the filesystem adapter and there should be no
        // duplicates on a concrete filesystem
        return new self($name, $files, false);
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
        return $this->mediaType;
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
        $file = $this->files->reduce(
            null,
            static function(?File $found, File $file) use ($name): ?File {
                if ($found) {
                    return $found;
                }

                if ($file->name()->equals($name)) {
                    return $file;
                }

                return null;
            }
        );

        return Maybe::of($file);
    }

    public function contains(Name $name): bool
    {
        return $this->files->reduce(
            false,
            static fn(bool $found, File $file): bool => $found || $file->name()->equals($name),
        );
    }

    public function remove(Name $name): DirectoryInterface
    {
        if (!$this->contains($name)) {
            return $this;
        }

        $directory = clone $this;
        $directory->files = $this->files->filter(static fn(File $file) => !$file->name()->equals($name));
        $directory->removed = ($directory->removed)($name);

        return $directory;
    }

    public function foreach(callable $function): void
    {
        $_ = $this->files->foreach($function);
    }

    public function filter(callable $predicate): self
    {
        // it is safe to not check for duplicates here as either the current
        // directory comes from the filesystem thus can't have duplicates or
        // comes from a user and must have used the standard constructor that
        // validates for duplicates, so they're can't be any duplicates after
        // a filter
        return self::defer($this->name, $this->files->filter($predicate));
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
