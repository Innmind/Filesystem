<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Event;

use Innmind\Filesystem\FileInterface;

final class FileWasAdded
{
    private $file;

    public function __construct(FileInterface $file)
    {
        $this->file = $file;
    }

    public function file(): FileInterface
    {
        return $this->file;
    }
}
