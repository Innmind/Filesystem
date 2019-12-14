<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\{
    File as FileInterface,
    Name,
    MediaType,
    MediaType\NullMediaType
};
use Innmind\Stream\Readable;

class File implements FileInterface
{
    private Name $name;
    private Readable $content;
    private MediaType $mediaType;

    public function __construct(
        string $name,
        Readable $content,
        MediaType $mediaType = null
    ) {
        $this->name = new Name\Name($name);
        $this->content = $content;
        $this->mediaType = $mediaType ?? new NullMediaType;
    }

    /**
     * {@inheritdo}
     */
    public function name(): Name
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
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
     *
     * @return self
     */
    public function withContent(Readable $content): self
    {
        $file = clone $this;
        $file->content = $content;

        return $file;
    }
}
