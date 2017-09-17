<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\EventBus\ContainsRecordedEventsInterface;

interface Directory extends File, ContainsRecordedEventsInterface, \Iterator, \Countable
{
    /**
     * Add a new file to the directory
     *
     * @param File $file
     *
     * @return self
     */
    public function add(File $file): self;

    /**
     * Return the file with the given name
     *
     * @param string $name
     *
     * @throws FileNotFoundException
     *
     * @return File
     */
    public function get(string $name): File;

    /**
     * Check if the file exists in the current directory
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Remove the wished file
     *
     * @param string $name
     *
     * @throws FileNotFoundException
     *
     * @return self
     */
    public function remove(string $name): self;

    /**
     * Replace a file at a given path
     *
     * @param string $path
     * @param File $file
     *
     * @return self
     */
    public function replaceAt(string $path, File $file): self;
}
