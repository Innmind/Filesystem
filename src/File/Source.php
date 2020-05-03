<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\{
    Source as SourceInterface,
    File,
    Name,
    Adapter,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable;
use Innmind\MediaType\MediaType;

final class Source implements SourceInterface
{
    private File $file;
    private Adapter $openedBy;
    private Path $path;

    public function __construct(
        File $file,
        Adapter $openedBy,
        Path $path
    ) {
        $this->file = $file;
        $this->openedBy = $openedBy;
        $this->path = $path;
    }

    public function sourcedAt(Adapter $adapter, Path $path): bool
    {
        return $this->openedBy === $adapter &&
            $this->path->equals($path);
    }

    public function name(): Name
    {
        return $this->file->name();
    }

    public function content(): Readable
    {
        return $this->file->content();
    }

    public function mediaType(): MediaType
    {
        return $this->file->mediaType();
    }
}
