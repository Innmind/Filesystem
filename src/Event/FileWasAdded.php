<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Event;

use Innmind\Filesystem\File;

final class FileWasAdded
{
    private $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function file(): File
    {
        return $this->file;
    }
}
