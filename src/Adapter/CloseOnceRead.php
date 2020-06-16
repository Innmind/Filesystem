<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Name,
};
use Innmind\Immutable\Set;

/**
 * Use this adapter to automatically close the opened stream once it has been
 * read once to avoid leaving too many opened resources (as it can cause a fatal)
 */
final class CloseOnceRead implements Adapter
{
    private Adapter $filesystem;

    public function __construct(Adapter $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): void
    {
        $this->filesystem->add($file);
    }

    /**
     * {@inheritdoc}
     */
    public function get(Name $file): File
    {
        return $this->wrap($this->filesystem->get($file));
    }

    /**
     * {@inheritdoc}
     */
    public function contains(Name $file): bool
    {
        return $this->filesystem->contains($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $file): void
    {
        $this->filesystem->remove($file);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Set
    {
        return $this
            ->filesystem
            ->all()
            ->map(fn(File $file): File => $this->wrap($file));
    }

    private function wrap(File $file): File
    {
        if ($file instanceof Directory) {
            return new Directory\CloseOnceRead($file);
        }

        return new File\CloseOnceRead($file);
    }
}
