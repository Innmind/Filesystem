<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\{
    File,
    Name,
    Stream\CloseOnEnd,
    Source,
    Adapter,
};
use Innmind\Stream\Readable;
use Innmind\MediaType\MediaType;
use Innmind\Url\Path;

final class CloseOnceRead implements File, Source
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function sourcedAt(Adapter $adapter, Path $path): bool
    {
        if (!$this->file instanceof Source) {
            return false;
        }

        return $this->file->sourcedAt($adapter, $path);
    }

    public function name(): Name
    {
        return $this->file->name();
    }

    public function content(): Readable
    {
        return new CloseOnEnd($this->file->content());
    }

    public function mediaType(): MediaType
    {
        return $this->file->mediaType();
    }
}
