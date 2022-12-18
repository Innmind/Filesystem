<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\File\Content;
use Innmind\MediaType\MediaType;

/**
 * @psalm-immutable
 */
interface File
{
    public function name(): Name;
    public function content(): Content;
    public function mediaType(): MediaType;
    public function rename(Name $name): self;

    /**
     * This method called on a directory DOES NOTHING
     */
    public function withContent(Content $content, MediaType $mediaType = null): self;
}
