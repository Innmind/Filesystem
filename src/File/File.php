<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\{
    File as FileInterface,
    Name,
};
use Innmind\Stream\Readable;
use Innmind\MediaType\MediaType;

final class File implements FileInterface
{
    private Name $name;
    private Readable $content;
    private MediaType $mediaType;

    public function __construct(
        Name $name,
        Readable $content,
        MediaType $mediaType = null
    ) {
        $this->name = $name;
        $this->content = $content;
        $this->mediaType = $mediaType ?? MediaType::null();
    }

    public static function named(
        string $name,
        Readable $content,
        MediaType $mediaType = null
    ): self {
        return new self(new Name($name), $content, $mediaType);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function content(): Readable
    {
        return $this->content;
    }

    public function mediaType(): MediaType
    {
        return $this->mediaType;
    }

    /**
     * New file reference with a different content
     */
    public function withContent(Readable $content): self
    {
        $file = clone $this;
        $file->content = $content;

        return $file;
    }
}
