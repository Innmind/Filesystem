<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\MediaType\{
    MediaType,
    NullMediaType
};

class File implements FileInterface
{
    private $name;
    private $content;
    private $mediaType;

    public function __construct(
        string $name,
        StreamInterface $content,
        MediaType $mediaType = null
    ) {
        $this->name = new Name($name);
        $this->content = $content;
        $this->mediaType = $mediaType ?? new NullMediaType;
    }

    /**
     * {@inheritdo}
     */
    public function name(): NameInterface
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function content(): StreamInterface
    {
        return $this->content;
    }

    public function mediaType(): MediaTypeInterface
    {
        return $this->mediaType;
    }

    /**
     * New file reference with a different content
     *
     * @return self
     */
    public function withContent(StreamInterface $content): self
    {
        $file = clone $this;
        $file->content = $content;

        return $file;
    }
}
