<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File;

use Innmind\Filesystem\{
    File as FileInterface,
    Name,
};
use Innmind\MediaType\MediaType;

/**
 * @psalm-immutable
 */
final class File implements FileInterface
{
    private Name $name;
    private Content $content;
    private MediaType $mediaType;

    public function __construct(
        Name $name,
        Content $content,
        MediaType $mediaType = null,
    ) {
        $this->name = $name;
        $this->content = $content;
        $this->mediaType = $mediaType ?? MediaType::null();
    }

    /**
     * @psalm-pure
     */
    public static function named(
        string $name,
        Content $content,
        MediaType $mediaType = null,
    ): self {
        return new self(Name::of($name), $content, $mediaType);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function content(): Content
    {
        return $this->content;
    }

    public function mediaType(): MediaType
    {
        return $this->mediaType;
    }
}
