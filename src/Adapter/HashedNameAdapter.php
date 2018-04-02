<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Name\Hashed,
    Name\Name,
    Exception\LogicException,
    Exception\FileNotFound,
};
use Innmind\Immutable\MapInterface;

/**
 * Take the name of a file hashes it and persist files in subdirectories
 * following the pattern /[ab]/[cd]/{remaining-of-the-hash}
 *
 * You can't add directories via this adapter
 */
final class HashedNameAdapter implements Adapter
{
    private $filesystem;

    public function __construct(Adapter $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function add(File $file): Adapter
    {
        if ($file instanceof Directory) {
            throw new LogicException;
        }

        $name = new Hashed($file->name());

        if ($this->filesystem->has($name->first())) {
            $first = $this->filesystem->get($name->first());
        } else {
            $first = new Directory\Directory($name->first());
        }

        if ($first->has($name->second())) {
            $second = $first->get($name->second());
        } else {
            $second = new Directory\Directory($name->second());
        }

        $this->filesystem->add(
            $first->add(
                $second->add(
                    new File\File(
                        $name->remaining(),
                        $file->content()
                    )
                )
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $file): File
    {
        if (!$this->has($file)) {
            throw new FileNotFound;
        }

        $originalName = $file;
        $name = new Hashed(new Name($file));
        $file = $this
            ->filesystem
            ->get($name->first())
            ->get($name->second())
            ->get($name->remaining());

        return new File\File(
            $originalName,
            $file->content(),
            $file->mediaType()
        );
    }

    public function has(string $file): bool
    {
        $name = new Hashed(new Name($file));

        if (!$this->filesystem->has($name->first())) {
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

        return $directory->has($name->remaining());
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $file): Adapter
    {
        if (!$this->has($file)) {
            throw new FileNotFound;
        }

        $name = new Hashed(new Name($file));
        $first = $this->filesystem->get($name->first());
        $first = $first->add(
            $first
            ->get($name->second())
            ->remove($name->remaining())
        );
        $this->filesystem->add($first);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): MapInterface
    {
        //this is not ideal but the names can't be determined from the hashes
        return $this->filesystem->all();
    }
}
