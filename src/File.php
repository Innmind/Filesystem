<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\File\Content;
use Innmind\MediaType\MediaType;

/**
 * @psalm-immutable
 */
final class File
{
    private Name $name;
    private Content $content;
    private MediaType $mediaType;

    private function __construct(
        Name $name,
        Content $content,
        ?MediaType $mediaType = null,
    ) {
        $this->name = $name;
        $this->content = $content;
        $this->mediaType = $mediaType ?? MediaType::null();
    }

    /**
     * @psalm-pure
     */
    public static function of(
        Name $name,
        Content $content,
        ?MediaType $mediaType = null,
    ): self {
        return new self($name, $content, $mediaType);
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function named(
        string $name,
        Content $content,
        ?MediaType $mediaType = null,
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

    public function rename(Name $name): self
    {
        return new self($name, $this->content, $this->mediaType);
    }

    public function withContent(Content $content, ?MediaType $mediaType = null): self
    {
        return new self(
            $this->name,
            $content,
            $mediaType ?? $this->mediaType,
        );
    }
}
