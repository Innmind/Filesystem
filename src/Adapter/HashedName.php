<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Name,
    Exception\LogicException,
    Exception\FileNotFound,
};
use Innmind\Immutable\{
    Set,
    Str,
};

/**
 * Take the name of a file hashes it and persist files in subdirectories
 * following the pattern /[ab]/[cd]/{remaining-of-the-hash}
 *
 * You can't add directories via this adapter
 */
final class HashedName implements Adapter
{
    private Adapter $filesystem;

    public function __construct(Adapter $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function add(File $file): void
    {
        if ($file instanceof Directory) {
            throw new LogicException('A directory can\'t be hashed');
        }

        $hashes = $this->hash($file->name());

        if ($this->filesystem->contains($hashes[0])) {
            $first = $this->filesystem->get($hashes[0]);
        } else {
            $first = new Directory\Directory($hashes[0]);
        }

        if ($first->contains($hashes[1])) {
            $second = $first->get($hashes[1]);
        } else {
            $second = new Directory\Directory($hashes[1]);
        }

        $this->filesystem->add(
            $first->add(
                $second->add(
                    new File\File(
                        $hashes[2],
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
        $hashes = $this->hash($file);
        $file = $this
            ->filesystem
            ->get($hashes[0])
            ->get($hashes[1])
            ->get($hashes[2]);

        return new File\File(
            $originalName,
            $file->content(),
            $file->mediaType(),
        );
    }

    public function contains(Name $file): bool
    {
        $hashes = $this->hash($file);

        if (!$this->filesystem->contains($hashes[0])) {
            return false;
        }

        $directory = $this->filesystem->get($hashes[0]);

        if (!$directory instanceof Directory) {
            return false;
        }

        $directory = $directory->get($hashes[1]);

        if (!$directory instanceof Directory) {
            return false;
        }

        return $directory->contains($hashes[2]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $file): void
    {
        if (!$this->contains($file)) {
            return;
        }

        $hashes = $this->hash($file);
        $first = $this->filesystem->get($hashes[0]);
        $first = $first->add(
            $first
                ->get($hashes[1])
                ->remove($hashes[2]),
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

    /**
     * @return array[Name, Name, Name]
     */
    private function hash(Name $name): array
    {
        $extension = \pathinfo($name->toString(), PATHINFO_EXTENSION);
        $hash = Str::of(\sha1(\pathinfo($name->toString(), PATHINFO_BASENAME)));

        $first = new Name($hash->substring(0, 2)->toString());
        $second = new Name($hash->substring(2, 2)->toString());
        $remaining = new Name($hash->substring(4)->toString().($extension ? '.'.$extension : ''));

        return [$first, $second, $remaining];
    }
}
