<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

interface DirectoryInterface extends FileInterface, \Iterator, \Countable
{
    /**
     * Add a new file to the directory
     *
     * @param FileInterface $file
     *
     * @return self
     */
    public function add(FileInterface $file): self;

    /**
     * Return the file with the given name
     *
     * @param string $name
     *
     * @throws FileNotFoundException
     *
     * @return FileInterface
     */
    public function get(string $name): FileInterface;

    /**
     * Check if the file exists in the current directory
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;
}