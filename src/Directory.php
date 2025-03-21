<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\DuplicatedFile;
use Innmind\Immutable\{
    Set,
    Sequence,
    Maybe,
    SideEffect,
};

/**
 * @psalm-immutable
 */
final class Directory
{
    private Name $name;
    /** @var Sequence<File|self> */
    private Sequence $files;
    /** @var Set<Name> */
    private Set $removed;

    /**
     * @param Sequence<File|self> $files
     * @param Set<Name> $removed
     */
    private function __construct(Name $name, Sequence $files, Set $removed)
    {
        $this->name = $name;
        $this->files = $files;
        $this->removed = $removed;
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<File|self>|null $files
     *
     * @throws DuplicatedFile
     */
    public static function of(Name $name, ?Sequence $files = null): self
    {
        return new self(
            $name,
            self::safeguard($files ?? Sequence::of()),
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
            Sequence::of(),
            Set::of(),
        );
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param Sequence<File|self> $files
     */
    public static function lazy(Name $name, Sequence $files): self
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

    public function rename(Name $name): self
    {
        return new self(
            $name,
            $this->files,
            $this->removed,
        );
    }

    public function add(File|self $file): self
    {
        return new self(
            $this->name,
            $this
                ->files
                ->filter(static fn(File|self $known): bool => !$known->name()->equals($file->name()))
                ->add($file),
            $this->removed,
        );
    }

    /**
     * @return Maybe<File|self>
     */
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

    public function remove(Name $name): self
    {
        return new self(
            $this->name,
            $this->files->filter(static fn(File|self $file) => !$file->name()->equals($name)),
            ($this->removed)($name),
        );
    }

    /**
     * @param callable(File|self): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        return $this->files->foreach($function);
    }

    /**
     * @param callable(File|self): bool $predicate
     */
    public function filter(callable $predicate): self
    {
        // it is safe to not check for duplicates here as either the current
        // directory comes from the filesystem thus can't have duplicates or
        // comes from a user and must have used the standard constructor that
        // validates for duplicates, so they're can't be any duplicates after
        // a filter
        return new self(
            $this->name,
            $this->files->filter($predicate),
            $this->removed,
        );
    }

    /**
     * @param callable(File|self): File $map
     *
     * @throws DuplicatedFile
     */
    public function map(callable $map): self
    {
        return new self(
            $this->name,
            self::safeguard($this->files->map($map)),
            $this->removed,
        );
    }

    /**
     * @param callable(File|self): self $map
     *
     * @throws DuplicatedFile
     */
    public function flatMap(callable $map): self
    {
        /** @var callable(File|self): Sequence<File|self> */
        $map = static fn(File|self $file): Sequence => $map($file)->all();

        return new self(
            $this->name,
            self::safeguard($this->files->flatMap($map)),
            $this->removed,
        );
    }

    /**
     * @template R
     *
     * @param R $carry
     * @param callable(R, File|self): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->files->reduce($carry, $reducer);
    }

    /**
     * This method should only be used for implementations of the Adapter
     * interface, normal users should never have to use this method
     *
     * @return Set<Name>
     */
    public function removed(): Set
    {
        return $this->removed;
    }

    /**
     * @return Sequence<File|self>
     */
    public function all(): Sequence
    {
        return $this->files;
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<File|self> $files
     *
     * @throws DuplicatedFile
     *
     * @return Sequence<File|self>
     */
    private static function safeguard(Sequence $files): Sequence
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
