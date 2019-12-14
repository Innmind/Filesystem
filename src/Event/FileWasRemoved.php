<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Event;

use Innmind\Filesystem\Name;

final class FileWasRemoved
{
    private Name $file;

    public function __construct(Name $file)
    {
        $this->file = $file;
    }

    public function file(): Name
    {
        return $this->file;
    }
}
