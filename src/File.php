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
    private function __construct(
        private Name $name,
        private Content $content,
        private MediaType $mediaType,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(
        Name $name,
        Content $content,
        ?MediaType $mediaType = null,
    ): self {
        return new self($name, $content, $mediaType ?? MediaType::null());
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    #[\NoDiscard]
    public static function named(
        string $name,
        Content $content,
        ?MediaType $mediaType = null,
    ): self {
        return self::of(Name::of($name), $content, $mediaType);
    }

    #[\NoDiscard]
    public function name(): Name
    {
        return $this->name;
    }

    #[\NoDiscard]
    public function content(): Content
    {
        return $this->content;
    }

    #[\NoDiscard]
    public function mediaType(): MediaType
    {
        return $this->mediaType;
    }

    #[\NoDiscard]
    public function rename(Name $name): self
    {
        return new self($name, $this->content, $this->mediaType);
    }

    #[\NoDiscard]
    public function withContent(Content $content, ?MediaType $mediaType = null): self
    {
        return new self(
            $this->name,
            $content,
            $mediaType ?? $this->mediaType,
        );
    }

    /**
     * @param callable(Content): Content $map
     */
    #[\NoDiscard]
    public function mapContent(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            $this->name,
            $map($this->content),
            $this->mediaType,
        );
    }
}
