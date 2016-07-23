<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

interface FileInterface
{
    /**
     * Return the name of the file
     *
     * @return NameInterface
     */
    public function name(): NameInterface;

    /**
     * Return the content stream of the file
     *
     * @return StreamInterface
     */
    public function content(): StreamInterface;

    public function mediaType(): MediaTypeInterface;
}
