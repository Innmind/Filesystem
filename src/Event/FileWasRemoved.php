<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Event;

final class FileWasRemoved
{
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function file(): string
    {
        return $this->file;
    }
}
