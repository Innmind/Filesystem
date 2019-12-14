<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Name\Hashed,
    Name,
    Exception\LogicException,
    Exception\FileNotFound,
};
use Innmind\Immutable\Set;

/**
 * Take the name of a file hashes it and persist files in subdirectories
 * following the pattern /[ab]/[cd]/{remaining-of-the-hash}
 *
 * You can't add directories via this adapter
 */
final class HashedNameAdapter implements Adapter
{
    private Adapter $filesystem;

    public function __construct(Adapter $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function add(File $file): void
    {
        if ($file instanceof Directory) {
            throw new LogicException;
        }

        $name = new Hashed($file->name());

        if ($this->filesystem->contains($name->first())) {
            $first = $this->filesystem->get($name->first());
        } else {
            $first = new Directory\Directory($name->first()->toString());
        }

        if ($first->contains($name->second())) {
            $second = $first->get($name->second());
        } else {
            $second = new Directory\Directory($name->second()->toString());
        }

        $this->filesystem->add(
            $first->add(
                $second->add(
                    new File\File(
                        $name->remaining()->toString(),
                        $file->content(),
                    ),
                ),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function get(Name $file): File
    {
        if (!$this->contains($file)) {
            throw new FileNotFound($file->toString());
        }

        $originalName = $file;
        $name = new Hashed($file);
        $file = $this
            ->filesystem
            ->get($name->first())
            ->get($name->second())
            ->get($name->remaining());

        return new File\File(
            $originalName->toString(),
            $file->content(),
            $file->mediaType(),
        );
    }

    public function contains(Name $file): bool
    {
        $name = new Hashed($file);

        if (!$this->filesystem->contains($name->first())) {
            return false;
        }

        $directory = $this->filesystem->get($name->first());

        if (!$directory instanceof Directory) {
            return false;
        }

        $directory = $directory->get($name->second());

        if (!$directory instanceof Directory) {
            return false;
        }

        return $directory->contains($name->remaining());
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $file): void
    {
        if (!$this->contains($file)) {
            return;
        }

        $name = new Hashed($file);
        $first = $this->filesystem->get($name->first());
        $first = $first->add(
            $first
                ->get($name->second())
                ->remove($name->remaining()),
        );
        $this->filesystem->add($first);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Set
    {
        //this is not ideal but the names can't be determined from the hashes
        return $this->filesystem->all();
    }
}
