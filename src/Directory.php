<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\EventBus\ContainsRecordedEventsInterface;

interface Directory extends File, ContainsRecordedEventsInterface, \Iterator, \Countable
{
    public function add(File $file): self;

    /**
     * @throws FileNotFound
     */
    public function get(string $name): File;
    public function has(string $name): bool;

    /**
     * @throws FileNotFound
     */
    public function remove(string $name): self;
    public function replaceAt(string $path, File $file): self;
}
